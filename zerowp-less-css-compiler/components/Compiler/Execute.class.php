<?php 
namespace ZeroWPLCC\Component\Compiler;

class Execute{

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontendCompiledCSS' ), 999 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'compilerScript' ), 199 );
		add_action( 'wp_head', array( $this, 'compilerPlaceholder' ), 199 );
		add_action( 'wp_ajax_zwplcc_save_compiled_css', array( $this, '_saveCompiledCss' ), 299 );
	}

	protected function singleCompilerArgs(){
		return array(
			// 'id' => , // A unique lowercase alpha-numeric ID. It's the handle for this stylesheet when it's compiled.
			// 'less_root' => , // The root folder where all less files are in.
			// 'less_file' => , // The main less file name from root folder.
			// 'replace' => , // The handle of a registered stylesheet to be replaced.
			// 'fields' => array(), // An array of fields with IDs from customizer and variable names to modify from less
		);
	}

	public function compilers(){
		return apply_filters( 'zwplcc:compilers', array() );
	}

	//------------------------------------//--------------------------------------//
	
	/**
	 * Front-end scripts & styles
	 *
	 * @return void 
	 */
	public function frontendCompiledCSS(){
		if( !empty( $this->compilers() ) ){
			
			foreach ( $this->compilers() as $compiler ) {
				if( file_exists( $this->_cssFilePath( $compiler['id'] ) ) ){

					if( !empty( $compiler['replace'] ) ){
						wp_deregister_style( $compiler['replace'] );
					}

					zwplcc()->addStyle( zwplcc_config( 'id' ) . '-compiled-css', array(
						'src'     => $this->_cssFileUrl(  $compiler['id']  ),
						'ver'     => get_option( 'zwplcc_compiled_css_version_'.  $compiler['id'], '0.1' ),
					));

				}
			}

		}
	}

	public function compilerPlaceholder(){
		if( is_customize_preview() ){
			if( !empty( $this->compilers() ) ){
				
				foreach ( $this->compilers() as $compiler ) {
					echo '<style id="less-css-renderer-placeholder-'. $compiler['id'] .'"></style>';
				}

			}
		}
	}

	public function compilerScript(){
		if( !empty( $this->compilers() ) ){

			zwplcc()->addScript( zwplcc_config( 'id' ) . '-compiler-config', array(
				'src' => zwplcc()->assetsURL( 'js/compiler-config.js' ),
				'ver' => zwplcc_config( 'version' ),
				'deps' => array( 'jquery' ),
				'zwplcc_config_admin' => array(
					'ajax_url'  =>  admin_url( 'admin-ajax.php' ),
					'compilers' => $this->compilers(),
				),
			));

		}
	}

	protected function _cssFile( $id, $type = 'dir' ){
		$type       = ( $type === 'url' ) ? 'baseurl' : 'basedir';
		$upload_dir = wp_upload_dir();
		$up_path    = trailingslashit($upload_dir[ $type ]);

		return $up_path . $id .'-custom.css';
	}

	public function _cssFilePath( $id ){
		return $this->_cssFile( $id, 'dir' );
	}

	public function _cssFileUrl( $id ){
		return $this->_cssFile( $id, 'url' );
	}

	public function _saveCompiledCss(){
		if( !empty( $this->compilers() ) ){
		
			$output = array();

			foreach ( $this->compilers() as $compiler ) {
				if( !empty( $_POST[ '_compiled_css' ][ $compiler['id'] ] ) ){
					$css        = wp_unslash( $_POST[ '_compiled_css' ][ $compiler['id'] ] );
					$css_file   = $this->_cssFilePath( $compiler['id'] );
					$status     = ( false !== file_put_contents( $css_file, $css ) ) ? 'success' : 'error';

					update_option( 'zwplcc_compiled_css_version'.  $compiler['id'], time() );

					// TODO: The following must be in a multidimensional array with each compiler

					$output[ $compiler['id'] ] = array(
						'status' => $status,
						'css_file' => $css_file,
					);
				}
			}

			echo json_encode( $output );

		}
		die();
	}

}