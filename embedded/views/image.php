<?php

// $cropped = types_image_crop( $img,
//            array(
//              'image_crop' => 'crop',
//              'return' => 'object',
//              'width' => 150,
//              'height' => 150
//            )
//      );
/**
 * API call.
 * 
 * @param type $img
 * @param type $args
 * @return type
 */
function types_image_resize( $img, $args = array() ) {
    WPCF_Loader::loadView( 'image' );
    $view = Types_Image_View::getInstance();
    $args = wp_parse_args( $args,
            array('return' => 'url', 'suppress_errors' => !TYPES_DEBUG) );
    $resized = $view->resize( $img, $args );
	$upload_dir = wp_upload_dir();
    if ( !is_wp_error( $resized ) ) {
        if ( is_string( $resized ) ) {
            /*$resized = (object) array(
                        'url' => WPCF_Path::getFileUrl( $resized, false ) . '/' . basename( $resized ),
                        'path' => $resized,
            );*/
            $image = basename( $resized );
            $resized = (object) array(
                        'url' => $upload_dir['baseurl'] . '/types_image_cache/' . $image ,
                        'path' => $upload_dir['basedir'] . '/types_image_cache/' . $image,
            );
        }
        else{
        	$image = basename( $resized->url );
        	$resized->url = $upload_dir['baseurl'] . '/types_image_cache/' . $image;
			$resized->path = $upload_dir['basedir'] . '/types_image_cache/' . $image;
			$resized->pathinfo['dirname'] = $upload_dir['baseurl'] . '/types_image_cache/';
        }
        switch ( $args['return'] ) {
            case 'object':
                return $resized;
                break;

            case 'path':
                return $resized->path;
                break;

            case 'url':
            default:
                return $resized->url;
                break;
        }
    } else if ( !$args['suppress_errors'] ) {
        return $resized;
    }
    return $img;
}

/**
 * API call.
 * 
 * @param type $img
 * @param type $args
 * @return type
 */
function types_image_crop( $img, $args = array() ) {
    return types_image_resize( $img, $args );
}

/**
 * Types Image View class.
 * 
 * @todo introduce __construct() to return static
 */
class Types_Image_View
{

    private static $__singleton;
    private static $__cache;

    /**
     * Construct.
     * 
     * @return type
     */
    private function __construct() {
        self::$__cache = new Types_Cache();
    }

    /**
     * Returns instance.
     * 
     * @return type
     */
    public static function getInstance() {
        if ( is_null( self::$__singleton ) ) {
            self::$__singleton = new Types_Image_View();
        }
        return self::$__singleton;
    }

    /**
     * Resize public method.
     * 
     * @param type $img
     * @param type $args
     * @return type
     */
    public function resize( $img, $args = array() ) {
        if ( $cached = self::$__cache->getCache( func_get_args() ) ) {
            return $cached;
        }
        return self::$__cache->setCache( func_get_args(),
                        self::__resizeImg( $img, $args ) );
    }

    /**
     * Crop public method.
     * 
     * @param type $img
     * @param type $args
     * @return type
     */
    public function crop( $img, $args = array() ) {
        return self::resize( $img, $args );
    }

    /**
     * Private resize method.
     * 
     * @param type $img
     * @param type $destination
     * @param type $args
     * @return type
     */
    private static function __resizeImg( $img, $args = array() ) {
        WPCF_Loader::loadClass( 'types_image_utils' );
        $utils = Types_Image_Utils::getInstance();
        if ( is_wp_error( $check = $utils->checkEditRequirements() ) ) {
            return $check;
        }
        if ( is_wp_error( $imgData = $utils->getImg( $img ) ) ) {
            return $imgData;
        }
        if ( is_wp_error( $path = $utils->getWritablePath( $img ) ) ) {
            return $path;
        }
        $args = wp_parse_args( $args,
                array(
            'resize' => 'proportional',
            'padding_color' => '#FFF',
            'clear_cache' => false,
            'width' => 250,
            'height' => 250,
                )
        );
        if ( intval( $args['width'] ) < 1 ) {
            $args['width'] = 1;
        }
        if ( intval( $args['height'] ) < 1 ) {
            $args['height'] = 1;
        }
        $dims = image_resize_dimensions( $imgData->width, $imgData->height,
                intval( $args['width'] ), intval( $args['height'] ),
                $args['resize'] == 'crop' );
        if ( !$dims ) {
            return new WP_Error( __CLASS__ . '::' . __METHOD__,
                    "Could not calculate resized image dimensions {$img}", $dims );
        }
        $basename = $utils->basename( $img, ".{$imgData->pathinfo['extension']}" );
        switch ( $args['resize'] ) {
            case 'stretch':
                $suffix = "{$args['width']}x{$args['height']}-stretched";
                break;
            
            case 'pad':
                $_padding_color_suffix = $args['padding_color'] == 'transparent' ? 'transparent' : hexdec( $args['padding_color'] );
                $suffix = "{$args['width']}x{$args['height']}-pad-"
                        . $_padding_color_suffix;
                break;

            default:
                $suffix = "{$dims[4]}x{$dims[5]}";
                break;
        }
        $croppedImg = "{$path}{$basename}-{$suffix}.{$imgData->pathinfo['extension']}";
        if ( !$args['clear_cache'] ) {
            if ( !is_wp_error( $cropped = $utils->getImg( $croppedImg ) ) ) {
                return $cropped;
            }
        }
        /*
         * 
         * Cropping
         */
        $imgRes = $utils->loadImg( $img );
        if ( !is_resource( $imgRes ) ) {
            return new WP_Error( __CLASS__ . '::' . __METHOD__,
                    "error_loading_image {$img}", $imgRes );
        }

        $dst_x = $dst_y = $src_x = $src_y = 0;
        $dst_w = intval( $args['width'] );
        $dst_h = intval( $args['height'] );
        $src_w = $imgData->width;
        $src_h = $imgData->height;

        switch ( $args['resize'] ) {
            case 'stretch':
                $new_image = wp_imagecreatetruecolor( $dst_w, $dst_h );
                break;

            case 'crop':
            default:
                list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;
                $new_image = wp_imagecreatetruecolor( $dst_w, $dst_h );
                break;

            case 'pad':
                $image_x = $imgData->width;
                $image_y = $imgData->height;
                $image_ar = $image_x / $image_y;
                $disp_x = intval( $args['width'] );
                $disp_y = intval( $args['height'] );
                $disp_ar = $disp_x / $disp_y;
                if ( $image_ar > $disp_ar ) {
                    $ratio = $disp_x / $image_x;
                    $dst_y = ($disp_y - $image_y * $ratio) / 2; // $offset_top
                    $dst_h = $disp_y - $dst_y * 2;
                } else {
                    $ratio = $disp_y / $image_y;
                    $dst_x = ($disp_x - $image_x * $ratio) / 2; // $offset_left
                    $dst_w = $disp_x - $dst_x * 2;
                }
                $new_image = wp_imagecreatetruecolor( intval( $args['width'] ),
                        intval( $args['height'] ) );
                if ( $args['padding_color'] == 'transparent' ) {
                    $t = imagecolorallocatealpha( $new_image, 255, 255, 255,
                            127 );
                    imagefill( $new_image, 0, 0, $t );
                    imagealphablending( $new_image, true );
                } else {
                    $rgb = $utils->hex2rgb( $args['padding_color'] );
                    $padding_color = imagecolorallocate( $new_image, $rgb['red'],
                            $rgb['green'], $rgb['blue'] );
                    imagefill( $new_image, 0, 0, $padding_color );
                }
                break;
        }

        $success = imagecopyresampled( $new_image, $imgRes,
                $dst_x, // dst_x x-coordinate of destination point.
                $dst_y, //dst_y y-coordinate of destination point.
                $src_x, //src_x x-coordinate of source point. ,
                $src_y, // src_y y-coordinate of source point.
                $dst_w, // dst_w Destination width.
                $dst_h, // dst_h Destination height.
                $src_w, // src_w Source width.
                $src_h// src_h Source height.
        );
        if ( !$success ) {
            imagedestroy( $imgRes );
            imagedestroy( $new_image );
            return new WP_Error( __CLASS__ . '::' . __METHOD__,
                    "Error resampling image {$img}", $dims );
        }

        // convert from full colors to index colors, like original PNG.
        if ( IMAGETYPE_PNG == $imgData->imagetype && function_exists( 'imageistruecolor' ) && !imageistruecolor( $imgRes ) ) {
            imagetruecolortopalette( $new_image, false,
                    imagecolorstotal( $imgRes ) );
        }
        // we don't need the original in memory anymore
        imagedestroy( $imgRes );

        try {

            if ( IMAGETYPE_GIF == $imgData->imagetype ) {
                if ( !imagegif( $new_image, $croppedImg ) ) {
                    throw new Exception();
                }
            } else if ( IMAGETYPE_PNG == $imgData->imagetype ) {
                if ( !imagepng( $new_image, $croppedImg ) ) {
                    throw new Exception();
                }
            } else {
                // all other formats are converted to jpg
                if ( 'jpg' != strtolower( $imgData->pathinfo['extension'] ) && 'jpeg' != strtolower( $imgData->pathinfo['extension'] ) ) {
                    $croppedImg = basename( $croppedImg,
                                    $imgData->pathinfo['extension'] ) . '.jpg';
                }
                if ( !imagejpeg( $new_image, $croppedImg,
                                apply_filters( 'jpeg_quality', 90,
                                        'image_resize' ) ) ) {
                    throw new Exception();
                }
            }
        } catch ( Exception $e ) {
            imagedestroy( $new_image );
            return new WP_Error( __CLASS__ . '::' . __METHOD__,
                    __( 'Resize path invalid' ), $croppedImg );
        }

        imagedestroy( $new_image );

        // Set correct file permissions
        $stat = stat( dirname( $croppedImg ) );
        //same permissions as parent folder, strip off the executable bits
        $perms = $stat['mode'] & 0000666;
        @chmod( $croppedImg, $perms );

        return $croppedImg;
    }

}

/**
 * Class Image Utilities.
 */
class Types_Image_Utils
{

    const DESTINATION_DIR = 'types_image_cache';

    private static $__singleton;
    private static $__cache;
    public static $errors;

    /**
     * Construct.
     * 
     * @return type
     */
    private function __construct() {
        WPCF_Loader::loadClass( 'path' );
        WPCF_Loader::loadModel( 'image' );
        self::$__cache = new Types_Cache();
        self::$errors = new Types_Error();
    }

    /**
     * Returns instance.
     * 
     * @return type
     */
    public static function getInstance() {
        if ( is_null( self::$__singleton ) ) {
            self::$__singleton = new Types_Image_Utils();
        }
        return self::$__singleton;
    }

    /**
     * Checks if all requirements for editing image are set.
     * 
     * @return \WP_Error|boolean
     */
    public static function checkEditRequirements() {
        if ( $cached = self::$__cache->getCache( 'check_edit_requirements' ) ) {
            return $cached;
        }
        self::$errors->clearErrors();
        // Check GD library
        if ( !extension_loaded( 'gd' ) || !function_exists( 'gd_info' ) ) {
            self::$errors->addError( 'GD library not present' );
        }
        // Check writable paths
        if ( is_wp_error( $path = self::getWritablePath() ) ) {
            self::$errors->addError( $path );
        }
        // Check if any errors
        if ( self::$errors->hasErrors() ) {
            return self::$__cache->setCache( 'check_edit_requirements',
                            self::$errors );
        }
        return self::$__cache->setCache( 'check_edit_requirements', true );
    }

    /**
     * Determines current writable path.
     * 
     * @param type $img
     * @return type
     */
    public static function getWritablePath( $img = null ) {
        if ( $cached = self::$__cache->getCache( 'writable_path' ) ) {
            return $cached;
        }
        $wpud = wp_upload_dir();
        $path = $wpud['basedir'];
        $dir = $path . DIRECTORY_SEPARATOR . self::DESTINATION_DIR . DIRECTORY_SEPARATOR;
        if ( !wp_mkdir_p( $dir ) || !is_writable( $dir ) || !is_dir( $dir ) ) {
            return self::$__cache->setCache( 'writable_path',
                            new WP_Error( __CLASS__ . '::' . __METHOD__,
                            'Can not create writable dir' ) );
        }
        return self::$__cache->setCache( 'temp_writable_path', $dir );
    }

    /**
     * Checks if image is valid.
     * 
     * @param type $img
     * @return type
     * @throws Exception
     */
    public static function getImg( $img ) {
        if ( $cached = self::$__cache->getCache( $img ) ) {
            return $cached;
        }
        self::$errors->clearErrors();
        try {
            if ( !is_file( $img ) || !is_readable( $img ) ) {
                self::$errors->addError( 'File not readable', $img );
            } else {
                if ( !@exif_imagetype( $img ) ) {
                    self::$errors->addError( 'File not image', $img );
                } else {
                    $size = @getimagesize( $img );
                    if ( !$size ) {
                        self::$errors->addError( 'Cannot read image size', $img );
                    }
                }
            }
            if ( self::$errors->hasErrors() ) {
                throw new Exception( 'Error found' );
            }
        } catch ( Exception $e ) {
            return self::$__cache->setCache( $img, self::$errors );
        }
        list($imgWidth, $imgHeight, $imgType) = $size;
        $data = array(
            'width' => $imgWidth,
            'height' => $imgHeight,
            'imagetype' => $imgType,
            'mime' => $size['mime'],
            'url' => WPCF_Path::getFileUrl( $img, false ) . '/' . basename( $img ),
            'path' => $img,
            'pathinfo' => pathinfo( $img ),
        );
        $imgData = new Types_Image_Model( (object) $data );
        return self::$__cache->setCache( $img, $imgData->getImg() );
    }

    /**
     * Loads image resource.
     * 
     * @param type $img
     * @return null
     */
    public static function loadImg( $img ) {
        if ( !is_file( $img ) || !function_exists( 'imagecreatefromstring' ) ) {
            return null;
        }
        // Set artificially high because GD uses uncompressed images in memory
        @ini_set( 'memory_limit',
                        apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
        $image = imagecreatefromstring( file_get_contents( $img ) );
        return $image;
    }

    /**
     * i18n friendly version of basename(), copy from wp-includes/formatting.php
     * to solve bug with windows
     * 
     * @param type $path
     * @param type $suffix
     * @return type
     */
    public static function basename( $path, $suffix = '' ) {
        return urldecode( basename( str_replace( array('%2F', '%5C'), '/',
                                urlencode( $path ) ), $suffix ) );
    }

    /**
     * Convert hex to RGB.
     * 
     * @param type $hex
     * @return type
     */
    public static function hex2rgb( $hex ) {
        $hex = str_replace( "#", "", $hex );
        if ( strlen( $hex ) == 3 ) {
            $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
            $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
            $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
        } else {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        }
        return array('red' => $r, 'green' => $g, 'blue' => $b);
}

}

/**
 * Image model class.
 */
class Types_Image_Model
{

    private $__img;

    /**
     * Init.
     * 
     * @return type
     */
    public function __construct( $img ) {
        $this->__img = $img;
    }

    /**
     * Returns image object.
     * 
     * @return type
     */
    public function getImg() {
        return $this->__img;
    }

}

/**
 * Types Cache class.
 */
class Types_Cache
{

    private $__cache = array();

    /**
     * Returns adjusted cache key.
     * 
     * @param type $key
     * @return type
     */
    private function __getCacheKey( $key ) {
        return md5( maybe_serialize( $key ) );
    }

    /**
     * Returns cache if available, otherwise false.
     * 
     * @param type $key
     */
    public function getCache( $key ) {
        $cache_key = $this->__getCacheKey( $key );
        return isset( $this->__cache[$cache_key] ) ? $this->__cache[$cache_key] : false;
    }

    /**
     * Sets cache.
     * 
     * @param type $key
     * @param type $data
     */
    public function setCache( $key, $data ) {
        $this->__cache[$this->__getCacheKey( $key )] = $data;
        return $data;
    }

}

/**
 * Types error class.
 */
class Types_Error extends WP_Error
{

    /**
     * Init function.
     * 
     * @param type $code
     * @param type $message
     * @param type $data
     */
    function __construct( $code = 'types_error', $message = '', $data = '' ) {
        parent::__construct( $code, $message, $data );
        $this->clearErrors();
    }

    /**
     * Adds error and debug data.
     * 
     * @param type $message
     * @param type $data
     */
    public function addError( $message, $data = array() ) {

        if ( $message instanceof WP_Error ) {
            $code = $message->get_error_code();
            $message = $message->get_error_message();
            $data = $message->error_data;
        } else {
            $db = debug_backtrace();
            $code = "{$db[1]['class']}::{$db[1]['function']}()";
        }
        parent::add( $code, $message, $data );
    }

    /**
     * Clear all errors (reset)
     */
    public function clearErrors() {
        $this->errors = array();
        $this->error_data = array();
    }

    /**
     * Returns errors (all codes).
     * 
     * @return type
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Checks if has errors.
     * 
     * @return type
     */
    public function hasErrors() {
        return (bool) count( $this->errors );
    }

}