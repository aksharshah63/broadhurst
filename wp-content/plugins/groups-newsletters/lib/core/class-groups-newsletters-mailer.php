<?php
/**
 * class-groups-newsletters-mailer.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// see ::send()
// require_once ABSPATH . WPINC . '/class-phpmailer.php';
// require_once ABSPATH . WPINC . '/class-smtp.php';
// class Groups_Newsletters_PHPMailer extends PHPMailer {
// 	public function __construct() {
// 		parent::__construct();

// 	}
// 	public function setMIMEHeader( $headers ) {
// 		$this->MIMEHeader = $headers;
// 	}

// 	public function GetMailMIME() {
// 		return '';
// 	}
// }

/**
 * Multipart mailer with independent SMTP option.
 */
class Groups_Newsletters_Mailer {

	/**
	 * PHPMailer
	 *
	 * @var object
	 */
	private static $mailer = null;

	/**
	 * Load PHPMailer.
	 *
	 * @return boolean success
	 */
	private static function load_mailer() {
		global $wp_version;

		$result = false;

		$legacy = false;
		if ( version_compare( $wp_version, '5.5.0' ) < 0 ) {
			$legacy = true;
		}

		if ( !$legacy ) {
			if ( !class_exists( '\PHPMailer\PHPMailer\PHPMailer' ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				class_alias( \PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
				class_alias( \PHPMailer\PHPMailer\SMTP::class, 'SMTP' );
				class_alias( \PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
			}
			if ( class_exists( '\PHPMailer\PHPMailer\PHPMailer' ) ) {
				$result = true;
			} else {
				error_log( 'Could not load the PHPMailer class.' );
			}
		} else {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			if ( class_exists( 'PHPMailer' ) ) {
				$result = true;
			} else {
				error_log( 'Could not load the legacy PHPMailer class.' );
			}
		}
		return $result;
	}

	/**
	 * Send an email.
	 *
	 * This generates a plain text version from the message and sends a
	 * multipart/alternative including text/plain and text/html parts.
	 *
	 * Use <br/> (not \r\n) as line breaks in message.
	 *
	 * @param string $email HTML is expected
	 * @param string $subject the email subject
	 * @param string $message the email message
	 * @param boolean $force use own SMTP settings even if disabled
	 */
	public static function mail( $email, $subject, $message, $force = false ) {

		$html_message = $message;

		$boundary_id = md5( time() );
		$boundary    = sprintf( 'groups-newsletters-%s', $boundary_id );

		// email headers
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";

		$content_start = 0;
		$content_end = strlen( $message ) - 1;
		$body_start = stripos( $message, '<body' );
		if ( $body_start !== false ) {
			$content_start = stripos( $message, '>', $body_start ) + 1;
			$body_end = stripos( $message, '</body>' );
			if ( $body_end !== false ) {
				$content_end = $body_end - 1;
			}
		}
		$id = '#!#' . md5( time() + rand( 0, time() ) ) . '#!#';
		$plain_message = substr( $message, $content_start, $content_end - $content_start + 1 );
		$plain_message = preg_replace( '/<a[^>]+href=\"(.+?)\"[^>]*>(.+?)<\/a>/ims', " $2 [$1] " , $plain_message );
		$plain_message = preg_replace( '/<h[1-6](.*?)>|<\/h[1-6]>/i', '$0' . $id, $plain_message );
		$plain_message = preg_replace( '/\\r\\n|\\r|\\n|<p>|<P>/', '', $plain_message );
		$plain_message = preg_replace( '/<br>|<br\/>|<BR>|<BR\/>|<\/p>|<\/P>/', "\r\n\r\n", $plain_message );
		$plain_message = wp_strip_all_tags( $plain_message );
		$plain_message = str_replace( $id, "\r\n\r\n", $plain_message );
		$plain_message = preg_replace_callback( "/(&#[0-9]+;)/", array( __CLASS__, 'decode' ), $plain_message );

		$message =
			"\r\n\r\n--" . $boundary . "\r\n" .
			'Content-type: text/plain; charset="' . get_option( 'blog_charset' ) . '"' . "\r\n\r\n" .
			$plain_message . "\r\n" .
			"\r\n\r\n--" . $boundary . "\r\n" .
			'Content-type: text/html; charset="' . get_option( 'blog_charset' ) . '"' . "\r\n\r\n" .
			$message . "\r\n" .
			"\r\n\r\n--" . $boundary . "--\r\n\r\n" ;

		$sent = false;
		$use_smtp = $force || Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_USE_SMTP, false );
		if ( $use_smtp ) {
// 			$sent = self::send( $email, $subject, $message, $headers, $boundary );
			$sent = self::send__( $email, $subject, $html_message, $plain_message );
		}
		if ( !$sent ) {
// 			@wp_mail( $email, wp_filter_nohtml_kses( $subject ),  $message, $headers );

			@wp_mail( $email, $subject, $html_message, sprintf( 'Content-type: text/html; charset="%s"' . "\r\n", get_option( 'blog_charset' ) ) );
		}

	}

	/**
	 * Numeric entities.
	 *
	 * @param array $s matches
	 */
	private static function decode( $s ) {
		$result = '';
		if ( !empty( $s[1] ) ) {
			$result = $s[1];
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$result = mb_convert_encoding( $s[1], get_bloginfo( 'charset' ), "HTML-ENTITIES" );
			} else {
				$result = htmlspecialchars_decode( $s[1] );
			}
		}
		return $result;
	}

	/**
	 * Send SMTP email.
	 *
	 * Currenty not used - issues with custom boundary and PHPMailer.
	 *
	 * @see Groups_Newsletters_Mailer::send__()
	 *
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 * @param string $headers
	 *
	 * @return boolean true on success, false otherwise
	 */
	private static function send( $email, $subject, $message, $headers = '', $boundary = '' ) {
		$sent = false;
		$smtp_email             = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_EMAIL, '' );
		$smtp_name              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_NAME, 'newsletters' );
		$smtp_set_return_path   = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH, false );
		$smtp_host              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_HOST, '' );
		$smtp_port              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PORT, '' );
		$smtp_encryption        = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_ENCRYPTION, null );
		$smtp_authentication    = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION, true );
		$smtp_user              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_USER, '' );
		$smtp_password          = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PASSWORD, '' );

		if ( !empty( $smtp_email ) && !empty( $smtp_host ) && !empty( $smtp_port ) ) {
			if ( !self::load_mailer() ) {
				return false;
			}
			if ( self::$mailer === null ) {
				try {
					self::$mailer = new PHPMailer();
// 					self::$mailer = new Groups_Newsletters_PHPMailer();
					self::$mailer->IsSMTP();
					self::$mailer->Host = $smtp_host;
					self::$mailer->Port = $smtp_port;
					if ( $smtp_authentication ) {
						self::$mailer->SMTPAuth = true;
						self::$mailer->Username = $smtp_user;
						self::$mailer->Password = $smtp_password;
					}
					switch ( $smtp_encryption ) {
						case 'ssl' :
						case 'tls' :
							self::$mailer->SMTPSecure = $smtp_encryption;
							break;
					}
					self::$mailer->From = $smtp_email;
					self::$mailer->FromName = $smtp_name;
					if ( $smtp_set_return_path ) {
						self::$mailer->Sender = $smtp_email;
					}

					$_headers = explode( "\r\n", $headers );
					foreach ( $_headers as $header ) {
						$h = explode( ':', trim( $header ), 2 );
						if ( isset( $h[0] ) && isset( $h[1] ) ) {
							self::$mailer->AddCustomHeader( trim( $h[0] ), trim( $h[1] ) );
						}
					}
					self::$mailer->ContentType = 'multipart/alternative' . ( !empty( $boundary ) ? '; boundary="' . $boundary . '"' . "\r\n" : '' );
					self::$mailer->CharSet = get_option( 'blog_charset' );
				} catch ( phpmailerException $e ) {
				}
			} else {
				try {
					self::$mailer->ClearAddresses();
					self::$mailer->ClearAllRecipients();
					self::$mailer->ClearAttachments();
					self::$mailer->ClearBCCs();
					self::$mailer->ClearCCs();
					self::$mailer->ClearCustomHeaders();
					self::$mailer->ClearReplyTos();
				} catch ( phpmailerException $e ) {
				}
			}

			try {
				self::$mailer->AddAddress( $email );
				self::$mailer->Subject = wp_filter_nohtml_kses( $subject );
				self::$mailer->Body = $message;

// 				if ( !empty( $headers)) {
// 				self::$mailer->setMIMEHeader($headers);
// 				}

				self::$mailer->Send();
				$sent = true;
			} catch ( phpmailerException $e ) {
			}
		}
		return $sent;
	}

	/**
	 * Send using PHPMailer's AltBody
	 *
	 * @param string $email email recipient
	 * @param string $subject email subject
	 * @param string $message HTML message
	 * @param string $plain_message plain text message
	 * 
	 * @return boolean success
	 */
	private static function send__( $email, $subject, $message, $plain_message ) {
		$sent = false;
		$smtp_email             = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_EMAIL, '' );
		$smtp_name              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_NAME, 'newsletters' );
		$smtp_set_return_path   = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH, false );
		$smtp_host              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_HOST, '' );
		$smtp_port              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PORT, '' );
		$smtp_encryption        = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_ENCRYPTION, null );
		$smtp_authentication    = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION, true );
		$smtp_user              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_USER, '' );
		$smtp_password          = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PASSWORD, '' );
		if ( !empty( $smtp_email ) && !empty( $smtp_host ) && !empty( $smtp_port ) ) {
			if ( !self::load_mailer() ) {
				return false;
			}
			if ( self::$mailer === null ) {
				try {
					self::$mailer = new PHPMailer();
					self::$mailer->isSMTP();
					self::$mailer->Host = $smtp_host;
					self::$mailer->Port = $smtp_port;
					if ( $smtp_authentication ) {
						self::$mailer->SMTPAuth = true;
						self::$mailer->Username = $smtp_user;
						self::$mailer->Password = $smtp_password;
					}
					switch ( $smtp_encryption ) {
						case 'ssl' :
						case 'tls' :
							self::$mailer->SMTPSecure = $smtp_encryption;
							break;
					}
					self::$mailer->From = $smtp_email;
					self::$mailer->FromName = $smtp_name;
					if ( $smtp_set_return_path ) {
						self::$mailer->Sender = $smtp_email;
					}
					if ( defined( 'GROUPS_NEWSLETTERS_SMTP_DEBUG' ) ) {
						$debug_level = 0;
						if ( is_bool( GROUPS_NEWSLETTERS_SMTP_DEBUG ) && GROUPS_NEWSLETTERS_SMTP_DEBUG ) {
							$debug_level = 2;
						} else if ( is_numeric( GROUPS_NEWSLETTERS_SMTP_DEBUG ) ) {
							$debug_level = intval( GROUPS_NEWSLETTERS_SMTP_DEBUG );
							if ( $debug_level < 0 ) {
								$debug_level = 0;
							}
						}
						if ( $debug_level > 0 ) {
							self::$mailer->SMTPDebug = $debug_level;
							self::$mailer->Debugoutput = 'error_log';
						}
					}
				} catch ( phpmailerException $e ) {
				}
			} else {
				try {
					self::$mailer->ClearAddresses();
					self::$mailer->ClearAllRecipients();
					self::$mailer->ClearAttachments();
					self::$mailer->ClearBCCs();
					self::$mailer->ClearCCs();
					self::$mailer->ClearCustomHeaders();
					self::$mailer->ClearReplyTos();
				} catch ( phpmailerException $e ) {
				}
			}
			try {
				self::$mailer->AddAddress( $email );
				self::$mailer->Subject = $subject;
				self::$mailer->AltBody = $plain_message;
				self::$mailer->MsgHTML( $message );
				self::$mailer->CharSet = get_option( 'blog_charset' );
				self::$mailer->Send();
				$sent = true;
			} catch ( phpmailerException $e ) {
			}
		}
		return $sent;
	}

	/**
	 * Send a test email.
	 *
	 * @param string $email recipient email address
	 * @return string empty on success or error info
	 */
	public static function test( $email ) {
		$result = '';
		self::mail(
			$email,
			__( 'Groups Newsletters Test Email', 'groups-newsletters' ),
			__( 'This is a test message.', 'groups-newsletters' ),
			true
		);
		if ( self::$mailer !== null && self::$mailer->IsError() ) {
			$result = self::$mailer->ErrorInfo;
		}
		return $result;
	}
}
