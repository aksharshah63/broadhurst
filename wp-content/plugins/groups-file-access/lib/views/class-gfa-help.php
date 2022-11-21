<?php
/**
 * class-gfa-file-renderer.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Context-specific help.
 */
class GFA_Help {

	/**
	* Renders the help section.
	*
	* @param string $what help
	*
	* @return string help markup
	*/
	public static function get_help( $context = null ) {
		$output = '';
		switch( $context ) {
			case 'groups-admin-files' :
				$output .= '<div class="manage gfa-help">';
				$output .= '<h3>' . esc_html__( 'Groups File Access', 'groups-file-access' ) . '</h3>';

				$output .= '<p>';
				$output .= wp_kses_post( __( 'Additional information and examples are available on the <a href="https://www.itthinx.com/plugins/groups-file-access/">plugin page</a> and the <a href="https://docs.itthinx.com/document/groups-file-access/">documentation pages</a>.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h4>' . esc_html__( 'Managing files', 'groups-file-access' ) . '</h4>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Files are managed in the <strong>Groups > Files</strong> section. Here you can add, edit and delete files that you want to make accessible to group members.', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Access to files is restricted by group membership. To be able to download a file, a user must be a member of a group that is assigned to the file. If an access limit has been set for the file, the user must also have accessed (downloaded) the file fewer times than the file&rsquo;s access limit.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'If you want to restrict access to a file to registered users, add the file using the <strong>New File</strong> option at the top of the screen. Once the file is added, tick the checkbox for the desired file in the file list, select the <em>Registered</em> group on top of the list and use the <strong>Add</strong> button to assign the group to the file. Now any registered user who is logged in can access the file (provided the access limit has not been exceeded by the user).', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'If you want to restrict access to a file to users that belong to a certain group, create the group, add the desired users to the group and assign the group to the files that the group should be able to access. More than one group can be assigned to a file.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'By selecting one or more files, bulk operations can be executed, including adding files to a group, removing files from a group and removing files.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<h5>' . esc_html__( 'Filters', 'groups-file-access' ) . '</h5>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Use the filters to restrict the files displayed in the file list to those that match the given criteria. Note that the filter settings are persistent, i.e. if you leave the screen or log out and come back, the same settings will be in effect. Click the <strong>Apply</strong> button to use the filters and the <strong>Clear</strong> button to remove all filters.', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '</div>';
				break;
			default :
				$output .= '<div class="manage gfa-help">';
				$output .= '<h3>' . esc_html__( 'Groups File Access', 'groups-file-access' ) . '</h3>';

				$output .= '<p>';
				$output .= wp_kses_post( __( 'Additional information and examples are available on the <a href="https://www.itthinx.com/plugins/groups-file-access/">plugin page</a> and the <a href="https://docs.itthinx.com/document/groups-file-access/">documentation pages</a>.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h4>' . esc_html__( 'Setup', 'groups-file-access' ) . '</h4>';

				$output .= '<ol>';
				$output .= '<li>';
				$output .= wp_kses_post( __( 'If you have not done so already, install and activate the <a href="https://www.itthinx.com/plugins/groups/">Groups</a> plugin. Go to <strong>Plugins > Add New</strong>, search for <em>Groups</em> and click <em>Install Now</em>. You can also download the plugin <a href="https://wordpress.org/plugins/groups/">here</a> and upload it to your site.', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( 'Check the settings on the <strong>Groups > File Access</strong> page and adjust them if needed.', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( 'If your setup is adequate, you can manage files controlled by the plugin on the <strong>Groups > Files</strong> page.', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= esc_html__( 'Use the shortcodes provided by the plugin to embed download links for group members on your pages or posts.', 'groups-file-access' );
				$output .= '</li>';
				$output .= '</ol>';

				$output .= '<h4>' . esc_html__( 'Managing files', 'groups-file-access' ) . '</h4>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Files are managed in the <strong>Groups > Files</strong> section. Here you can add, edit and delete files that you want to make accessible to group members.', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Access to files is restricted by group membership. To be able to download a file, a user must be a member of a group that is assigned to the file. If an access limit has been set for the file, the user must also have accessed (downloaded) the file fewer times than the file&rsquo;s access limit.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'If you want to restrict access to a file to registered users, add the file using the <strong>New File</strong> option at the top of the screen. Once the file is added, tick the checkbox for the desired file in the file list, select the <em>Registered</em> group on top of the list and use the <strong>Add</strong> button to assign the group to the file. Now any registered user who is logged in can access the file (provided the access limit has not been exceeded by the user).', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'If you want to restrict access to a file to users that belong to a certain group, create the group, add the desired users to the group and assign the group to the files that the group should be able to access. More than one group can be assigned to a file.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'By selecting one or more files, bulk operations can be executed, including adding files to a group, removing files from a group and removing files.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<h5>' . esc_html__( 'Filters', 'groups-file-access' ) . '</h5>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Use the filters to restrict the files displayed in the file list to those that match the given criteria. Note that the filter settings are persistent, i.e. if you leave the screen or log out and come back, the same settings will be in effect. Click the <strong>Apply</strong> button to use the filters and the <strong>Clear</strong> button to remove all filters.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h4>' . esc_html__( 'Shortcodes', 'groups-file-access' ) . '</h4>';
				$output .= '<p>';
				$output .= esc_html__( 'Shortcodes are used on posts or pages to render links to files, provide information about files and conditionally show content to users depending on whether they are allowed to access a file.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'The <code>[groups_file_link]</code> shortcode described below, renders the actual link to a file that authorized users can click to download the file.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_can_access_file]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= esc_html__( 'Content enclosed by this shortcode will only be shown if the current user can access the file. The file is identified by the required <code>file_id</code> attribute.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Example: <code>[groups_can_access_file file_id="3"]This is shown if the user can access the file.[/groups_can_access_file]</code>', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Attributes', 'groups-file-access' );
				$output .= '<br/>';
				$output .= '<ul>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>file_id</code> required - identifies the desired file', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '</ul>';
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_can_not_access_file]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Shows content enclosed by the shortcode only when the current user can not access the given file. Attributes and usage are the same as for the <code>[groups_can_access_file]</code> shortcode.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_file_info]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= esc_html__( 'This shortcode renders information about a file including the name, description, maximum number of allowed accesses per user, consumed and remaining number of accesses for the current user and the file id.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Example: <code>[groups_file_info file_id="7" show="name"]</code>', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Attributes', 'groups-file-access' );
				$output .= '<br/>';
				$output .= '<ul>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>file_id</code> required - identifies the desired file', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>show</code> optional - defaults to "name". Acceptable values are any of <code>name</code>, <code>description</code>, <code>count</code>, <code>max_count</code>, <code>remaining</code> and <code>file_id</code>', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>visibility</code> optional - defaults to <code>can_access</code> showing information only if the current user can access the file, <code>always</code> will show information unconditionally', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>filter</code> optional - defaults to <code>wp_filter_kses</code> determining the filter function that is applied to the information about to be shown. If <code>none</code> or an empty value is provided, no filter function will be applied prior to rendering the information.', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '</ul>';
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_file_url]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= esc_html__( 'This shortcode renders the URL that serves the file. An authorized user can visit the URL to download the file.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Example: <code>[groups_file_url file_id="456"]</code>', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Attributes', 'groups-file-access' );
				$output .= '<br/>';
				$output .= '<ul>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>file_id</code> required - identifies the desired file', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>visibility</code> optional - defaults to <code>can_access</code> showing information only if the current user can access the file, <code>always</code> will show information unconditionally', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '</ul>';
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_file_link]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= esc_html__( 'This shortcode renders links to files. An authorized user can click on a link to download the related file.', 'groups-file-access' );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Example: <code>[groups_file_link file_id="78"]</code>', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Attributes', 'groups-file-access' );
				$output .= '<br/>';
				$output .= '<ul>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>file_id</code> required* - identifies the desired file', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>group</code> required* - group name or ID - will list file links for the files that are related to the given group, sorted by name', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>description</code> optional - only effective when used with the <code>group</code> attribute; defaults to "no", if set to "yes" will show descriptions for each file', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>order</code> optional - only effective when used with the <code>group</code> attribute; files are listed sorted by name which defaults to "asc" for ascending order, allows "desc" for descending order', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>visibility</code> optional - defaults to <code>can_access</code> showing information only if the current user can access the file, <code>always</code> will show information unconditionally', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '</ul>';
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( '* Only one of <code>file_id</code> or <code>group</code> is required, both should not be provided.', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Additional attributes are accepted for the link&rsquo;s <code>a</code> tag. These are <code>accesskey</code>, <code>alt</code>, <code>charset</code>, <code>coords</code>, <code>class</code>, <code>dir</code>, <code>hreflang</code>, <code>id</code>, <code>lang</code>, <code>name</code>, <code>rel</code>, <code>rev</code>, <code>shape</code>, <code>style</code>, <code>tabindex</code> and <code>target</code>. Please refer to the <a href="http://www.w3.org/TR/html401/cover.html">HTML 4.01 Specification</a> on the <a href="http://www.w3.org/TR/html401/struct/links.html#h-12.2">The A element</a> for further information about these attributes.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '<h5>' . esc_html__( '[groups_file_visibility]', 'groups-file-access' ) .'</h5>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'This shortcode allows to switch the default visibility setting for those shortcodes that provide a <code>visibility</code> attribute. The shortcode can be used multiple times on a page or post and will affect the shortcodes below it unless it is used again.', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Example: <code>[groups_file_visibility visibility="always"]</code>', 'groups-file-access' ) );
				$output .= '</p>';
				$output .= '<p>';
				$output .= esc_html__( 'Attributes', 'groups-file-access' );
				$output .= '<br/>';
				$output .= '<ul>';
				$output .= '<li>';
				$output .= wp_kses_post( __( '<code>visibility</code> required - <code>can_access</code> or <code>always</code>', 'groups-file-access' ) );
				$output .= '</li>';
				$output .= '</ul>';
				$output .= '</p>';

				$output .= '<h4>' . esc_html__( 'API', 'groups-file-access' ) . '</h4>';
				$output .= '<p>';
				$output .= wp_kses_post( __( 'Please refer to the <a href="https://docs.itthinx.com/document/groups-file-access/">documentation pages</a> for details on the plugin&rsquo;s API.', 'groups-file-access' ) );
				$output .= '</p>';

				$output .= '</div>';

				break;
		}
		return $output;
	}

	/**
	 * Provide the footer code.
	 *
	 * @param boolean $show_icon
	 *
	 * @return string
	 */
	public static function footer( $show_icon = false ) {
		$output = '<div class="gfa-footer">';
		if ( $show_icon ) {
			$output .= sprintf( '<img src="%s://www.itthinx.com/img/groups/gfa.png">', is_ssl() ? 'https' : 'http' );
		}
		$output .= sprintf(
			esc_html__( 'Copyright %1$sitthinx%2$s - This plugin is provided subject to the license granted. Unauthorized use and distribution is prohibited.', 'groups-file-access' ),
			'<a style="text-decoration:none;color:inherit;font-weight:bold;" href="https://www.itthinx.com/">',
			'</a>'
		);
		$output .= '</div>';
		return $output;
	}
}
