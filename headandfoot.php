<?php
/*
Plugin Name: Header and Footer Snippets
Plugin Script: headandfoot.php
Plugin URI: http://formula04.com
Description: Add snippets of code to the header and footer of your site as a whole or individual pages.
Version: 0.9
Author: VerB
Author URI: https://profiles.wordpress.org/verb_hf_snippets


=== RELEASE NOTES ===
2017-10-18 - v0.1 - first version
2017-10-20 - v0.9 - 
*/
// uncomment next line if you need functions in external PHP script;
// include_once(dirname(__FILE__).'/some-library-in-same-folder.php');


if(  !class_exists( 'HF_Snippets'  )  ):
class HF_Snippets {


	/* __construct function.
     *
     * @access public
     * @param mixed $product
     */
    public function __construct()
    {
		//When Plugins get loaded - Earliest plugin hook I believe
		//Add our custom product type
	
		//Add scripts to admin footer		
        add_action('admin_footer', array(
            &$this,
            'hf_snippets_admin_scripts_func'
        ));
		
		//Enqueue Admin scripts.
        add_action('admin_head', array(
            &$this,
            'hf_snippets_admin_head'
        ));		
		
		//Add scripts to admin footer		
        add_action('admin_footer', array(
            &$this,
            'hf_snippets_admin_scripts_func'
        ));	
		
		
		//Add snippets to header on front-end
		add_action('wp_enqueue_scripts', array(
			&$this,
			'enqueue_scripts_func'
		));		
		
		//Save custom metabox data		
		add_action('save_post', array(
			&$this,
			'hf_save_meta_box_data'
		), 10);       
		
		//Add meta boxes to correct pages.
		add_action('add_meta_boxes', array(
			&$this,
			'hf_add_meta_boxes'
		), 10);       
		
				
		//Add Settings Page
		add_action('admin_menu', array(
			&$this,
			'hf_create_menu'
		), 10);       
		
	}//__construct


/**
 * Add meta box
 *
 * @param post $post The post object
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
 */
public function hf_add_meta_boxes( $post ){
	
	//Which post types do we want to enable snippets for.
	$args = array(
	   'public'   => true,
	   '_builtin' => true
	);
	$output = 'names'; // names or objects, note names is the default
	$operator = 'or'; // 'and' or 'or'

	$post_types = get_post_types( $args, $output, $operator ); 
	
	add_meta_box( 'hf_meta_box', __( 'Header and Footer Snippets', 'hf_snippets' ), array(&$this,'hf_build_meta_box'), $post_types, 'normal', 'low' );
}

/**
 * Build custom field meta box
 *
 * @param post $post The post object
 */
public function hf_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'hf_meta_box_nonce' );
	
	// retrieve the header snippets
	$header_snippets = esc_html(get_post_meta( $post->ID, 'header_snippets', true ));
	
	// retrieve the header snippets priority
	$hs_priority = get_post_meta( $post->ID, 'hs_priority', true );
	
	// retrieve the footer snippets
	$footer_snippets = esc_html(get_post_meta( $post->ID, 'footer_snippets', true ));
	
	// retrieve the footer snippets priority
	$fs_priority = get_post_meta( $post->ID, 'fs_priority', true );
	
	?>
		
	<div class='inside' id="headandfoot_admin">

		<h3><?php _e( 'Header Snippets', 'hf_snippets' ); ?></h3>
		<p>
			<textarea id="hs_ta" class="large-text txt_area" name="header_snippets"><?php echo $header_snippets; ?></textarea>			
		</p>
		
		<h3><?php _e( 'Header Appearance Priority', 'hf_snippets' ); ?></h3>
		<p>
			<input type="number" name="hs_priority" value="<?php echo intval($hs_priority); ?>" min="0" placeholder="10" /> 
		</p>

		<hr />
		<h3><?php _e( 'Footer Snippets', 'hf_snippets' ); ?></h3>
		<p>
			<textarea id="fs_ta" class="large-text txt_area" name="footer_snippets"><?php echo $footer_snippets; ?></textarea>			
		</p>
		
		<h3><?php _e( 'Footer Appearance Priority', 'hf_snippets' ); ?></h3>
		<p>
			<input type="number" name="fs_priority" value="<?php echo intval($fs_priority); ?>" min="0"  placeholder="10"/> 
		</p>
			
	</div>
	
	

	<?php
}
/**
 * Store custom field meta box data
 *
 * @param int $post_id The post ID.
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
 */
public function hf_save_meta_box_data( $post_id ){
	// verify meta box nonce
	if ( !isset( $_POST['hf_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['hf_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	
	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}

	// Check the user's permissions. MAKE sure they have administrator rights.
	if ( ! current_user_can( 'administrator', $post_id ) ){
		return;
	}	
	
	//Get allowed POST TAGS
	global $allowedposttags;
	
	$orginal_allowedposttags = $allowedposttags;
	//Determine what other tags to allow
	$allowedposttags['script'] = array(
	 'type' => array(),
	 'src' => array()
	);		
	$allowedposttags['div'] = array(
	 'class' => array(),
	 'id' => array()
	);
		
	//TODO : Allow end user to choose which tags to allow.
	$hf_allowedposttags = apply_filters('hf_allowedposttags',  $allowedposttags, $orginal_allowedposttags); 
		
	if ( isset( $_REQUEST['header_snippets'] ) ) {
		$header_snippets = htmlentities ($_REQUEST['header_snippets']);
		update_post_meta( $post_id, 'header_snippets', ( $header_snippets ) );
	}else{
		// delete data
		delete_post_meta( $post_id, 'header_snippets' );
	}
	if ( isset( $_REQUEST['hs_priority'] ) ) {
		$hs_priority = (int)sanitize_text_field( $_POST['hs_priority'] ); 
		update_post_meta( $post_id, 'hs_priority', $hs_priority );
	}else{
		// delete data
		delete_post_meta( $post_id, 'hs_priority' );
	}
		
	if ( isset( $_REQUEST['footer_snippets'] ) ) {
		
		$footer_snippets = htmlentities ($_REQUEST['footer_snippets']);
		update_post_meta( $post_id, 'footer_snippets', ( $footer_snippets ) );
	
	}else{
		// delete data
		delete_post_meta( $post_id, 'footer_snippets' );
	}
	
	if ( isset( $_REQUEST['fs_priority'] ) ) {
		$fs_priority  =  (int)sanitize_text_field( $_POST['fs_priority'] );
		update_post_meta( $post_id, 'fs_priority', $fs_priority);
	}else{
		// delete data
		delete_post_meta( $post_id, 'fs_priority' );
	}
	
}//public function hf_save_meta_box_data( $post_id ){

//Add inline admin scripts 
public function hf_snippets_admin_scripts_func(){
?><script type="text/javascript">
		<?php //Expand Text areas on input ?>
		var textareas = document.getElementById('headandfoot_admin').getElementsByClassName('txt_area');
		var heightLimit = 400; /* Maximum height: 200px */
		Array.prototype.forEach.call(textareas, function(textarea) {

			//Resize Intial box
			textarea.style.height = ""; /* Reset the height*/
			textarea.style.height = Math.min(textarea.scrollHeight, heightLimit) + "px";


			// Do Resize on input
			textarea.oninput = function($dis) {
			textarea.style.height = ""; /* Reset the height*/
			textarea.style.height = Math.min(textarea.scrollHeight, heightLimit) + "px";
			};

		});//Array.prototype.forEach.call(textareas, function(textarea) {

	</script><?php
}//end public function hf_snippets_admin_scripts_func(){

//Add inline css to admin 
public function hf_snippets_admin_head(){
ob_start();	
?><style type="text/css"> 
		#headandfoot_admin td[scope="row"]{
		font-size: 1.2em;
		padding-bottom: 0px;
		font-weight: bold;color: #37a714;
		}
		#headandfoot_admin input[type=number],
		#headandfoot_admin textarea{
		background-color: rgba(35, 40, 45, 0.06);}
</style>
	<?php
$hf_admin_css = ob_get_contents();	
ob_end_clean();
	
echo apply_filters('hf_admin_css', $hf_admin_css);	
	
}//public function hf_snippets_admin_head(){
	
	
	
public function enqueue_scripts_func(){
	
	global $post;
	$post_id = $post != NULL && property_exists($post, 'ID') ? $post->ID : false;
	
	if( $post_id ):	
		// retrieve the header snippets
		$header_snippets = get_post_meta( $post->ID, 'header_snippets', true );
		// retrieve the header snippets priority
		//Worpress doesnt handle negative priority or a priority of 1?  
		$hs_priority =  intval(get_post_meta( $post->ID, 'hs_priority', true ));
		$hs_priority =  $hs_priority > 1 ? $hs_priority : '10' ;
		$hs_priority = apply_filters('hs_priority', $hs_priority, $post_id);	
			
		if(  $header_snippets  ):	
			//echo $header_snippets;
			add_action('wp_head', function(){
				ob_start();
				global $post;		

				$post_id = $post != NULL && property_exists($post, 'ID') ? $post->ID : false;
				$header_snippets = get_post_meta( $post->ID, 'header_snippets', true );

				do_action('before_header_snippet', $post);		

				$header_snippets = apply_filters('header_snippets', $header_snippets, $post);

				echo html_entity_decode ($header_snippets);

				do_action('after_header_snippet', $post, $header_snippets);			

				$content = ob_get_contents();
				ob_end_clean();


				$content = apply_filters('header_snippets_content', $content);
				echo $content;


			}, $hs_priority);	
	
		endif;//if($header_snippets):
	
	
		//retrieve the footer snippets
		$footer_snippets = get_post_meta( $post->ID, 'footer_snippets', true );
		// retrieve the header snippets priority
		//Worpress doesnt handle negative priority or a priority of 1?  
		$fs_priority =  intval(get_post_meta( $post->ID, '$fs_priority', true ));
		$fs_priority =  $fs_priority > 1 ? $fs_priority : '10' ;
		$fs_priority = apply_filters('fs_priority', $fs_priority, $post_id);	
		

		if(  $fs_priority  ):	
			//echo $header_snippets;
			add_action('wp_footer', function(){
				ob_start();
				global $post;		

				$post_id = $post != NULL && property_exists($post, 'ID') ? $post->ID : false;
				$footer_snippets = get_post_meta( $post->ID, 'footer_snippets', true );

				do_action('before_footer_snippet', $post);		

				$footer_snippets = apply_filters('footer_snippets', $footer_snippets, $post);
					echo html_entity_decode ( $footer_snippets );
				do_action('after_footer_snippet', $post, $footer_snippets);			

				$content = ob_get_contents();
				ob_end_clean();


				$content = apply_filters('footer_snippets_content', $content);
				echo $content;

			}, $fs_priority);
	
		endif;//if($fs_priority):	
	
	endif;//if($post_id):
	
	
	//Global Header and Footer
	//retrieve the global Header snippets
	$global_header_snippets = get_option( 'global_header_snippets' );
	$global_hs_priority = get_option( 'global_hs_priority' ) && get_option( 'global_hs_priority' ) > 1 ? intval( get_option( 'global_hs_priority' ) ) : '10';

	if(  $global_header_snippets  ):

		//echo $header_snippets;
	add_action('wp_head', function(){
		ob_start();

		$global_header_snippets = get_option( 'global_header_snippets' );

		do_action('before_global_header_snippets', $global_header_snippets);		

		$global_header_snippets = apply_filters('global_header_snippets', $global_header_snippets);
				echo html_entity_decode( $global_header_snippets );
		do_action('after_global_header_snippets', $global_header_snippets);			

		$content = ob_get_contents();
		ob_end_clean();


		$content = apply_filters('global_header_snippets_content', $content);
		echo $content;

	}, $global_hs_priority);


	endif;//if($global_header_snippets):


	//retrieve the Global footer snippets
	$global_footer_snippets = get_option( 'global_footer_snippets' );
	$global_fs_priority = get_option( 'global_fs_priority' ) && get_option( 'global_fs_priority' ) > 1 ? get_option( 'global_fs_priority' ) : '10';


	if(  $global_footer_snippets  ):	
		//echo $header_snippets;
	add_action('wp_footer', function(){
		ob_start();

		$global_footer_snippets = get_option( 'global_footer_snippets' );

		do_action('before_global_footer_snippets', $global_footer_snippets);		

		$global_footer_snippets = apply_filters('global_footer_snippets', $global_footer_snippets);
				echo (html_entity_decode ( $global_footer_snippets ));
		do_action('after_global_footer_snippets', $global_footer_snippets);			

		$content = ob_get_contents();
		ob_end_clean();


		$content = apply_filters('global_footer_snippets_content', $content);
		echo $content;

	}, $global_fs_priority);


	endif;//if($global_footer_snippets):	
	
}	//public function enqueue_scripts_func(){

	
	
	

public function hf_settings_page() {
?>
<h2><?php _e('Global Header and Footer Snippets Settings', 'headnfoot') ?></h2>
<p><?php _e('These Global snippets will appear across your entire site.  If you just want to add snippets to individual pages, use the Header and Footer Snippet metaboxes on the edit screen of the desired pages.', 'headnfoot') ?></p>

<form method="post" action="options.php" id="headandfoot_admin">
    <?php settings_fields( 'hf-settings-group' ); ?>
		<table class="form-table widefat" style="padding: 20px;">
			<tr valign="top">
			<td scope="row" colspan="2"><?php _e('Global Header Snippets', 'headnfoot'); ?></td>
			 </tr>
			   <tr valign="top">
			<td colspan="2">

			<textarea name="global_header_snippets" class="txt_area large-text"><?php echo get_option('global_header_snippets'); ?></textarea>
			<p class="description"><?php _e('These <strong>HEADER</strong> snippets will appear in the header of every page on your site that uses "wp_head"', 'headnfoot'); ?></p>

			</td>
			</tr>

			<tr valign="top">
			<td scope="row"><?php _e('Global Header Snippets Priority', 'headnfoot'); ?></td>
			<tr/>
			<tr>
			<td><input type="number" name="global_hs_priority" value="<?php echo get_option('global_hs_priority'); ?>" min="0" placeholder="10" /></td>
			</tr>		
		</table>
		<table class="form-table widefat" style="padding: 20px;">
			<tr valign="top">
				<td scope="row"><?php _e('Global Footer Snippets', 'headnfoot'); ?></td>
			</tr>
			<tr valign="top">
			<td colspan="2">
			<textarea name="global_footer_snippets" class="txt_area large-text"><?php echo get_option('global_footer_snippets'); ?></textarea>
			<p class="description"><?php _e('These <strong>FOOTER</strong> snippets will appear in the footer of every page on your site that uses "wp_footer"', 'headnfoot'); ?></p>
			</td>

			</tr>


			<tr valign="top">
			<td scope="row"><?php _e('Global Footer Snippets Priority', 'headnfoot'); ?></td>
			<tr/>
			<tr>
			<td><input type="number" name="global_fs_priority" value="<?php echo get_option('global_fs_priority'); ?>" min="0" placeholder="10" /></td>
			</tr>
		</table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
<?php } //public function hf_settings_page() {	

public function register_thesettings() {
	//register our settings
	register_setting( 'hf-settings-group', 'global_header_snippets' );
	register_setting( 'hf-settings-group', 'global_footer_snippets' );
	register_setting( 'hf-settings-group', 'global_hs_priority' );
	register_setting( 'hf-settings-group', 'global_fs_priority' );
}//public function register_thesettings() {
	
function hf_create_menu() {
	//create new top-level menu
	//Make sure the user has administrator permissions.
	add_menu_page('HF Plugin Settings', 'Header and Footer Snippets Settings', 'administrator', __FILE__, array(&$this,'hf_settings_page'),'dashicons-editor-code');
	//call register settings function
	add_action( 'admin_init', array(&$this,'register_thesettings') );
}//function hf_create_menu() {
	
static public function _activate(){
}
static public function _deactivate(){
}
static public function _uninstall(){
}

   
} // end of class HF_Snippets
endif;

register_activation_hook( __FILE__, array( 'HF_Snippets', '_activate' ) );	
register_deactivation_hook( __FILE__, array( 'HF_Snippets', '_deactivate' ) );
register_uninstall_hook( __FILE__, array( 'HF_Snippets', '_uninstall' ) );	
new  HF_Snippets; 

add_filter('footer_snippets', 'test_footer_snippets_filter', 10, 2);
function test_footer_snippets_filter($footer_snippets, $global_post){
	return $footer_snippets;
}

add_filter('header_snippets', 'test_header_snippets_filter', 10, 2);
function test_header_snippets_filter($header_snippets, $global_post){
	return $header_snippets;
}