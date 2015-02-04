<?php

class Installer_Deps_Loader{

    private $config = array();
    private $missing = array();

    function __construct(){

        add_action('admin_init', array($this, 'init'), 30);

	    add_filter('installer_deps_missing', array($this, 'get_missing_deps'));

    }

    public function init(){

        add_action('wp_ajax_wp_installer_fix_deps', array($this, 'run'));

        $config_file = WP_Installer()->plugin_path() . '/deps.xml';

        if(file_exists($config_file) && is_readable($config_file)) {

            $this->config = $this->read_config($config_file);

            foreach($this->config as $repository_id => $repository){

                foreach($repository['plugins'] as $plugin){

                    $plugin_full_name = $this->get_plugin_full_name($repository_id, $plugin['name']);
                    if(!$plugin_full_name) continue;

                    if(!$this->is_plugin_installed($plugin['name'])){
                        $this->missing[] = array(
                                'basename'      => $plugin['name'],
                                'name'          => $plugin_full_name,
                                'url'           => $this->get_plugin_download_url($repository_id, $plugin['name']),
                                'repository_id' => $repository_id,
                                'status'        => __('not installed', 'installer')
                        );
                    }elseif(!$this->is_plugin_active($plugin['name'])){
                        $this->missing[] = array(
                                'basename'      => $plugin['name'],
                                'name'          => $plugin_full_name,
                                'url'           => $this->get_plugin_download_url($repository_id, $plugin['name']),
                                'repository_id' => $repository_id,
                                'status'        => __('inactive', 'installer')
                        );
                    }elseif(!empty($plugin['version']) && $plugin['version'] != 'latest'){
                        if(!$this->is_plugin_installed($plugin['name'], $plugin['version'], '>')) {
                            $this->missing[] = array(
                                    'basename'      => $plugin['name'],
                                    'name'          => $plugin_full_name,
                                    'url'           => $this->get_plugin_download_url($repository_id, $plugin['name']),
                                    'repository_id' => $repository_id,
                                    'status'        => __('out of date', 'installer')
                            );
                        }
                    }

                }

            }

        }

        if($this->missing){
            add_action('admin_notices', array($this, 'setup_notice'));
            add_action('admin_footer', array($this, 'js_footer'));

        }else{

        }


    }

    public function read_config($config_file){

        $repositories = array();

        $repositories_xml = simplexml_load_file($config_file);

        $array = json_decode(json_encode($repositories_xml), true);
        $array = $array['repository'];

        $repositories_arr = isset($array[0]) ? $array : array($array);

        foreach($repositories_arr as $r){
            $r['plugins'] = isset($r['plugins']['plugin'][0]) ? $r['plugins']['plugin'] : array($r['plugins']['plugin']);

            $repositories[$r['id']] = $r;
        }

        return $repositories;

    }

    public function get_missing_deps(){
        return $this->missing;
    }

    public function setup_notice(){
        ?>
        <div class="updated" id="wp_installer_fix_deps_notice" >
            <p><?php printf(__('%s needs these plugins to work:', 'installer'), wp_get_theme()); ?></p>
            <ul>
                <?php foreach($this->missing as $p): ?>
                <li>
                    <?php echo $p['name'] ?> (<?php echo $p['status'] ?>)
                    <?php if(!WP_Installer()->is_uploading_allowed()): ?>
                    | <a href="<?php echo $p['url'] ?>"><?php _e('Download', 'installer') ?></a>
                    <?php endif; ?>
                </li>
                <?php endforeach;?>
            </ul>

            <?php if(!WP_Installer()->is_uploading_allowed()): ?>
                <p class="installer-warn-box">
                    <?php _e('Automatic downloading is not possible because WordPress cannot write into the plugins folder. Please use the download links above to get the zip files, unpack and upload to the plugins folder. If folders with the same name exist, please replace with the new ones.', 'installer') ?>
                </p>
            <?php endif; ?>

            <p class="submit">
            <input id="wp_installer_fix_deps" type="button" class="button-primary" value="<?php esc_attr_e('Install', 'installer') ?>" <?php
            disabled(!WP_Installer()->is_uploading_allowed()); ?> />
            <span class="spinner"></span>&nbsp;<span id="wp_installer_fix_deps_status"></span>
            </p>
        </div>

        <?php

    }

    public function is_plugin_installed($basename, $version = false, $compare = '='){

        $is = false;
        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){

            if(dirname($plugin_id) == $basename){
                if($version !== false ){
                    if(version_compare($plugin_data['Version'], $version, $compare)){
                        $is = true;
                    }
                }else{
                    $is = true;

                }
                break;

            }
        }

        return $is;

    }

    public function is_plugin_active($basename){

        $is = false;
        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){
            if(dirname($plugin_id) == $basename && is_plugin_active($plugin_id)){
                $is = true;
                break;

            }
        }

        return $is;

    }

    public function get_plugin_id($basename){

        $plugin_wp_id = false;

        $plugins = get_plugins();
        foreach($plugins as $plugin_id => $plugin_data){
            if(dirname($plugin_id) == $basename){
                $plugin_wp_id = $plugin_id;
                break;

            }
        }

        return $plugin_wp_id;

    }

	public function run(){

        $config_file = WP_Installer()->plugin_path() . '/deps.xml';
        $this->config = $this->read_config($config_file);

        $return['stop'] = 0;

        foreach($this->config as $repository_id => $repository){

            $downloads = $this->get_repository_downloads($repository_id);

            foreach($repository['plugins'] as $plugin){

	            if(!isset($downloads[$plugin['name']])) continue;

                if(!$this->is_plugin_installed($plugin['name'])){

                    $ret = WP_Installer()->download_plugin($downloads[$plugin['name']]['basename'],
                            $downloads[$plugin['name']]['url']);
                    if($ret){
                        $return['status_message'] = sprintf(__('Installed %s', 'installer'), $downloads[$plugin['name']]['name']);
                    }else{
                        $return['status_message'] = sprintf(__('Failed to download %s', 'installer'), $downloads[$plugin['name']]['name']);
                        $return['stop'] = 1;
                    }
                    break; // one operation at the time

                }elseif(!$this->is_plugin_active($plugin['name'])){

                    if($plugin_wp_id = $this->get_plugin_id($plugin['name'])){
                        //prevent redirects
                        add_filter('wp_redirect', '__return_false');
                        
                        $ret = activate_plugin($plugin_wp_id);
                        $return['status_message'] = sprintf(__('Activated %s', 'installer'), $downloads[$plugin['name']]['name']);
                    }else{

                        $return['status_message'] = sprintf(__('Plugin not found: %s', 'installer'), $downloads[$plugin['name']]['name']);
                        $return['stop'] = 1;
                    }
                    break; // one operation at the time

                }elseif(!empty($plugin['version']) && $plugin['version'] != 'latest'){
                    if(!$this->is_plugin_installed($plugin['name'], $plugin['version'], '>')) {

                        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                        require_once WP_Installer()->plugin_path() . '/includes/installer-upgrader-skins.php';

                        $upgrader_skins = new Installer_Upgrader_Skins(); //use our custom (mute) Skin
                        $upgrader = new Plugin_Upgrader($upgrader_skins);

                        remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

                        $plugin_wp_id = $this->get_plugin_id($plugin['name']);
                        $ret = $upgrader->upgrade($plugin_wp_id);
                        if($ret){
                            $return['status_message'] = sprintf(__('Upgraded %s', 'installer'), $downloads[$plugin['name']]['name']);
                        }else{
                            $return['status_message'] = sprintf(__('Failed to upgrade %s', 'installer'), $downloads[$plugin['name']]['name']);
                            $return['stop'] = 1;
                        }

                    }
                    break; // one operation at the time

                }

            }

        }

        if(empty($return['status_message'])){
            $return['status_message'] = __('Operation complete!', 'installer');
            $return['status_message'] .=  '&nbsp;<a href="#" id="wp_installer_fix_deps_dismiss">'. __('Dismiss', 'installer') . '</a>';
            $return['stop'] = 1;

        }

        echo json_encode($return);
        exit;

	}

    public function get_repository_downloads($repository_id){

        if(!isset($this->repository_downloads[$repository_id])) {

            $downloads = array();
            $installer_settings = WP_Installer()->get_settings();

            if (isset($installer_settings['repositories'][$repository_id])) {

                foreach ($installer_settings['repositories'][$repository_id]['data']['packages'] as $package) {

                    foreach ($package['products'] as $product) {

                        foreach ($product['downloads'] as $download) {

                            if (!isset($downloads[$download['basename']])) {

                                $d['name'] = $download['name'];
                                $d['basename'] = $download['basename'];
                                $d['version'] = $download['version'];
                                $d['date'] = $download['date'];
                                $d['url'] = $download['url'] . '&theme_key=' . $this->config[$repository_id]['key']
                                        . '&theme_name=' . urlencode(wp_get_theme());

                                $downloads[$d['basename']] = $d;
                            }

                        }

                    }

                }

            }

            $this->repository_downloads[$repository_id] = $downloads;

        }

        return $this->repository_downloads[$repository_id];

    }

    public function get_plugin_download_url($repository_id, $basename){

        $downloads = $this->get_repository_downloads($repository_id);

        return isset($downloads[$basename]) ? $downloads[$basename]['url'] : false;

    }

    public function get_plugin_full_name($repository_id, $basename){

        $downloads = $this->get_repository_downloads($repository_id);

        return isset($downloads[$basename]) ? $downloads[$basename]['name'] : false;

    }

    public function js_footer(){
        ?>
        <script type='text/javascript'>
        /* <![CDATA[ */

        jQuery('#wp_installer_fix_deps').click(function(){

            jQuery('#wp_installer_fix_deps').attr('disabled', 'disabled');
            jQuery('#wp_installer_fix_deps_notice').find('.spinner').addClass('spinner-inline').show();

            wp_installer_deps_load_run();
            return false;
        })

        function wp_installer_deps_load_run(){

            jQuery.ajax({
                url:        ajaxurl,
                type:       'post',
                dataType:   'json',
                data:       {action: 'wp_installer_fix_deps'},
                success: function(ret){

                    jQuery('#wp_installer_fix_deps_status').html(ret.status_message);

                    if(ret.stop){
                        jQuery('#wp_installer_fix_deps_notice').find('.spinner').removeClass('spinner-inline').hide();

                    }else{

                        wp_installer_deps_load_run();
                    }

                }
            })
        }

        jQuery('#wp_installer_fix_deps_status').on('click', '#wp_installer_fix_deps_dismiss', function(){
	        jQuery('#wp_installer_fix_deps_notice').fadeOut();
	        return false;
        })

        /* ]]> */
        </script>
        <?php
    }

}
