<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Model
{

    /**
     * 
     * @var CI_Config
     */
    private $_config;

    private $_readonly;
    // Add one of:
    // if ( $this->READONLY ) { return FALSE; }
    // if ( $this->READONLY ) { return NULL; }
    // to any method which should not be callable in a Read-Only instance of
    // the User class. The method will always return FALSE or NULL if READONLY is TRUE
    
    // --------------------------------------------------------------------- //
    // ------------------------- Account Variables ------------------------- //
    // --------------------------------------------------------------------- //
    private $_guid;

    private $_username;

    private $_email;

    private $_state;

    private $_lastIP;

    private $_lastLogin;

    private $_joinDate;
    
    private $_securityQuestion1;
    
    private $_securityQuestion2;
    
    private $_securityQuestion3;
    
    private $_securityAnswer1;
    
    private $_securityAnswer2;
    
    private $_securityAnswer3;
    
    private $_privateKey;
    
    private $_authCode;
    
    // --------------------------------------------------------------------- //
    // ------------------------- Profile Variables ------------------------- //
    // --------------------------------------------------------------------- //

    private $_firstName;

    private $_lastName;
    
    private $_streetAddress;
    
    private $_optionalAddress;
    
    private $_postalCode;
    
    private $_city;
    
    private $_country;
    
    private $_billingFirstName;
    
    private $_billingLastName;
    
    private $_billingStreetAddress;
    
    private $_billingOptionalAddress;
    
    private $_billingPostalCode;
    
    private $_billingCity;
    
    private $_billingCountry;
    
    private $_homePhone;
    
    private $_mobilePhone;
    
    private $_workPhone;

    private $_titles = array();
    
    
    // --------------------------------------------------------------------- //
    // -------------------------- Other Variables -------------------------- //
    // --------------------------------------------------------------------- //
    
    // --------------------------------------------------------------------- //
    // ----------------------------- CONSTRUCT ----------------------------- //
    // --------------------------------------------------------------------- //
    public function __construct ( $guid = "" )
    {
        parent::__construct();
        
        $this->_config = new CI_Config();
        
        if ( $guid != "" ) {
            // We want to load the userdata of another user. This instance is readonly.
            // Methods that alter database information should be forced to exit early or return false.
            $this->_readonly = TRUE;
            
            $sql = "SELECT COUNT(*) as count FROM `accounts` WHERE `guid` = UNHEX(?)";
            $arr = array(
                $guid
            );
            $rs = $this->db->query( $sql, $arr );
            $row = $rs->row();
            
            if ( $row->count == 1 ) {
                $row = $rs->row();
                
                $this->set_guid( $guid );
                $this->update_account_data();
                $this->update_profile_data();
                $this->update_titles();
            }
        } else {
            $this->_readonly = FALSE;
            if ( !class_exists('Session') ) {
                $this->load->library( 'session' );
            }
            
            if ( $this->is_logged_in() ) {
                $this->set_guid( $_SESSION['guid'] );
                $this->update_account_data();
                $this->update_profile_data();
                $this->update_titles();
            }
            
            if ( $this->is_admin() ) {
                $_SESSION['api_authorized'] = TRUE;
            }
            
            if ( $this->is_whitelist_active() && ! $this->is_whitelisted_ip() 
                && ( ! isset( $_POST['login_username'] ) ) ) {
                if ( $this->is_logged_in() ) {
                    if ( ! $this->is_whitelisted_username() ) {
                        redirect( $this->_config->item( 'whitelist_redirect' ) );
                        exit();
                    }
                } else {
                    redirect( $this->_config->item( 'whitelist_redirect' ) );
                    exit();
                }
            }
        }
    }
    
    // --------------------------------------------------------------------- //
    // -------------------------- Private Methods -------------------------- //
    // --------------------------------------------------------------------- //
    /**
     * Return True if the user account data was retrieved, False otherwise.
     *
     * @access private
     * @return boolean
     */
    public function update_account_data ()
    {
        $sql = "SELECT `username`, `email`, `state`, `last_ip`, `last_login_datetime`, `created_datetime`,
                        `security_question_1`, `security_question_2`, `security_question_3`,
                        `security_answer_1`, `security_answer_2`, `security_answer_3`, 
                        `private_key`, `auth_code`
		      FROM `accounts` WHERE `guid` = UNHEX(?)";
        $arr = array(
            $this->get_guid()
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() == 1 ) {
            $row = $query->row();
            
            $this->set_username( $row->username );
            $this->set_email( $row->email );
            $this->set_state( $row->state );
            $this->set_last_ip( $row->last_ip );
            $this->set_last_login( $row->last_login_datetime );
            $this->set_join_date( $row->created_datetime );
            $this->set_security_question_1( $row->security_question_1 );
            $this->set_security_question_2( $row->security_question_2 );
            $this->set_security_question_3( $row->security_question_3 );
            $this->set_security_answer_1( $row->security_answer_1 );
            $this->set_security_answer_2( $row->security_answer_2 );
            $this->set_security_answer_3( $row->security_answer_3 );
            $this->set_private_key( $row->private_key );
            $this->set_auth_code( $row->auth_code );
            
            return TRUE;
        } else {
            
            return FALSE;
        }
    }

    /**
     * Return True if the user profile data was retrieved, False otherwise.
     *
     * @access private
     * @return boolean
     */
    public function update_profile_data ()
    {
        $sql = "SELECT * FROM `user_profiles` WHERE `guid` = UNHEX(?)";
        $arr = array(
            $this->get_guid()
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() == 1 ) {
            $row = $query->row();
            
            $this->set_first_name( $row->first_name );
            $this->set_last_name( $row->last_name );
            $this->set_street_address( $row->street_address );
            $this->set_optional_address( $row->optional_address );
            $this->set_city( $row->city );
            $this->set_postal_code( $row->postal_code );
            $this->set_country( $row->country );
            $this->set_home_phone( $row->home_phone );
            $this->set_mobile_phone( $row->mobile_phone );
            $this->set_work_phone( $row->work_phone );
            
            $this->set_billing_first_name( $row->billing_first_name );
            $this->set_billing_last_name( $row->billing_last_name );
            $this->set_billing_street_address( $row->billing_street_address );
            $this->set_billing_optional_address( $row->billing_optional_address );
            $this->set_billing_postal_code( $row->billing_postal_code );
            $this->set_billing_country( $row->billing_country );
            $this->set_billing_city( $row->billing_city );
            
            return TRUE;
        } else {
            
            return FALSE;
        }
    }

    public function update_titles ()
    {
        $sql = "SELECT `title` FROM `user_titles` WHERE `guid` = UNHEX(?) AND `state` = '1'";
        $arr = array(
            $this->get_guid()
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() > 0 ) {
            
            foreach ( $query->result() as $row ) {
                $this->_titles[] = $row->title;
            }
            
            return TRUE;
        } else {
            $this->_titles = array();
            
            return FALSE;
        }
    }
    
    // --------------------------------------------------------------------- //
    // --------------------------- Public Methods -------------------------- //
    // --------------------------------------------------------------------- //
    public function require_login ()
    {
        if ( ! $this->is_logged_in() ) {
            redirect( base_url() . 'login?e=100&redirect=' 
                . base_url( uri_string() ) );
            exit();
        }
    }

    /**
     * TODO: Return True if the user was successfully registerred, 
     * False otherwise.
     *
     * It would be better to pass an array of information to the function 
     * register().
     *
     * @access public
     * @return boolean
     */
    public function register ( $username, $password, $password_confirm, $email, $first_name, $last_name, $street_address, $street_address_opt, $country, $city, $postal_code, $day_phone )
    {
        if ( $this->_readonly ) {
            return array(
                FALSE,
                "",
                ""
            );
        }
        
        $this->load->helper('date');
        $Input = new CI_Input();
        
        
        $err = "";
        $valid_fields = array();
        $valid_fields['username'] = "";
        $valid_fields['email'] = "";
        $valid_fields['first_name'] = "";
        $valid_fields['last_name'] = "";
        $valid_fields['street_address'] = "";
        $valid_fields['street_address_opt'] = "";
        $valid_fields['country'] = "";
        $valid_fields['city'] = "";
        $valid_fields['postal_code'] = "";
        $valid_fields['day_phone'] = "";
        
        // ----- REQUIRED FIELDS MUST NOT BE EMPTY -----//
        if ( empty( $first_name ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $last_name ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $username ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $email ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $password ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $password_confirm ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $street_address ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $country ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $city ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $postal_code ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        if ( empty( $day_phone ) ) {
            $err = $this->gen_err_string( $err, 106 );
        }
        
        // ----- VALIDATE CONTENT OF FIELDS ----- //
        if ( ! $this->is_valid_username( $username ) || ! $this->is_unique_username( $username ) ) {
            $err = $this->gen_err_string( $err, 101 );
        } else {
            $valid_fields['username'] = $username;
        }
        if ( ! $this->is_valid_email( $email ) ) {
            $err = $this->gen_err_string( $err, 103 );
        } else {
            $valid_fields['email'] = $email;
        }
        if ( ! $this->is_valid_password( $password ) ) {
            $err = $this->gen_err_string( $err, 102 );
        }
        if ( $password != $password_confirm ) {
            $err = $this->gen_err_string( $err, 104 );
        }
        if ( ! $this->is_unique_email( $email ) ) {
            $err = $this->gen_err_string( $err, 105 );
            $valid_fields['email'] = "";
        }
        if ( ! $this->is_valid_name( $first_name ) ) {
            $err = $this->gen_err_string( $err, 106 );
        } else {
            $valid_fields['first_name'] = $first_name;
        }
        if ( ! $this->is_valid_name( $last_name ) ) {
            $err = $this->gen_err_string( $err, 106 );
        } else {
            $valid_fields['last_name'] = $last_name;
        }
        
        if ( $err == "" ) {
            $guid = $this->gen_guid();
            $hash = $this->gen_secure_hash( $password );
            // $private_key = $this->gen_token('key', $uid, $email);
            $auth_code = $this->gen_auth_code();
            
            $account_sql = "INSERT INTO `accounts` 
			        (`guid`, `username`, `password`, `email`, 
			        `state`, `created_datetime`, `auth_code`)
			    VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?)";
            $account_data = array(
                $guid,
                $username,
                $hash,
                $email,
                'inactive',
                date ('Y-m-d H:i:s', now()),
                $auth_code
            );
            
            if ( ! $this->db->query( $account_sql, $account_data ) ) {
                // GENERATE ERROR
                return FALSE;
            }
            
            $profile_sql = "INSERT INTO `user_profiles`
					   (`guid`, `first_name`, `last_name`, `street_address`,
                        `optional_address`, `postal_code`, `city`, `country`,
                        `home_phone`)
					VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?)";
            $profile_data = array(
                $guid,
                $first_name,
                $last_name,
                $street_address,
                $street_address_opt, 
                $postal_code,
                $city,
                $country,
                $day_phone
            );
            if ( ! $this->db->query( $profile_sql, $profile_data ) ) {
                // GENERATE ERROR
                return FALSE;
            }
            
            $Mail = new Mail();
            $Mail->create();
            if ( ! $Mail->sendRegisterActivate($guid) ) {
                return array(
                    FALSE,
                    '125',
                    ''
                );
            }
            
            $apiKey = $this->gen_token( 'api', $guid, $email );
            $sql = "INSERT INTO `api_keys` (`api_key`, `guid`, `level`, `ignore_limits`, `is_private_key`, `ip_addresses`, `date_created`)
                        VALUES (?, UNHEX(?), ?, ?, ?, ?, ?)";
            $arr = array(
                $apiKey,
                $guid,
                '1',
                '0',
                '0',
                NULL,
                now()
            );
            $this->db->query( $sql, $arr );
            
            return array(
                TRUE,
                '',
                ''
            );
            
        } else {
            $token = $this->gen_token();
            
            $sql_tmp = "INSERT INTO `tmp_registrations`
				(`token`, `ip_address`, `username`, `email`, `first_name`, `last_name`,
					`street_address`, `street_address_opt`, `country`, `city`, 
                    `postal_code`, `day_phone`, `created_datetime`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $arr_tmp = array(
                $token,
                $Input->ip_address(),
                $valid_fields['username'],
                $valid_fields['email'],
                $valid_fields['first_name'],
                $valid_fields['last_name'],
                $street_address,
                $street_address_opt,
                $country,
                $city,
                $postal_code,
                $day_phone,
                date ('Y-m-d H:i:s', now())
            );
            
            $this->db->query( $sql_tmp, $arr_tmp );
            
            return array(
                FALSE,
                $err,
                $token
            );
        }
    }

    /**
     * Return True if the user was successfully invited, False otherwise.
     *
     * @access public
     * @param string $email            
     * @param string $subject            
     * @param string $message            
     * @return boolean
     */
    public function invite ( $email, $subject, $message )
    {
        if ( $this->_readonly ) {
            return FALSE;
        }
        
        if ( $this->is_valid_email( $email ) && $this->is_unique_email( $email ) ) {
            
            $guid = $this->gen_guid();
            $key = ""; // Don't have any use for this right now. Removed to reduce script time.
            $auth_code = ""; // Don't need this because invited users are activated on registration.
            
            $sql_acct = "INSERT INTO `accounts`
					(`guid`, `email`,
					`state`, `created`, `private_key`, `auth_code`)
					VALUES (UNHEX(?), ?, ?, ?, ?, ?)";
            $arr_acct = array(
                $guid,
                $email,
                'invited',
                date( 'Y-m-d H:i:s', now() ),
                $key,
                $auth_code
            );
            if ( ! $this->db->query( $sql_acct, $arr_acct ) ) {
                // GENERATE ERROR
                return FALSE;
            }
            
            $sql_pro = "INSERT INTO `user_profiles`
					(`guid`)
					VALUES (UNHEX(?))";
            $arr_pro = array(
                $guid
            );
            if ( ! $this->db->query( $sql_pro, $arr_pro ) ) {
                // GENERATE ERROR
                return FALSE;
            }
            
            $token = $this->gen_token( 'invite', $guid );
            $url = "http://www.uconnexion.com/register?bk=" . $token;
            $details = "<br /><br /><h2>Registration Details:</h2>" . "<strong>Email: </strong>" . $email . "<br /><strong>Beta Key: </strong>" . $token . "<br /><br />" . "Please follow the link below to create your account.<br />" . "<strong>URL: </strong>{unwrap}<a href=\"" . $url . "\">" . $url . "</a>{/unwrap}";
            
            $this->load->library( 'email' );
            
            $this->email->from( 'support@uconnexion.com', 'UConnexion' );
            $this->email->to( $email );
            
            $this->email->subject( $subject );
            $this->email->message( $message . $details );
            
            if ( ! $this->email->send() ) {
                // GENERATE ERROR
                return FALSE;
            }
            
            return TRUE;
        } else {
            
            return FALSE;
        }
    }

    /**
     * Return True if the user was successfully logged in, False otherwise.
     *
     * @access public
     * @param string $username            
     * @param string $password            
     * @return boolean
     */
    public function login ( $username, $password )
    {
        if ( $this->_readonly ) {
            return array(
                FALSE,
                ""
            );
        }
        
        $Input = new CI_Input();
        
        $sql = "SELECT HEX(`guid`) AS 'guid', `password`, `state` FROM `accounts` WHERE `username` = ? LIMIT 1";
        $arr = array(
            $username
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() == 1 ) {
            $row = $query->row();
            
            if ( $this->validate_password( $row->password, $password ) ) {
                if ( $row->state == "active" || $row->state == "restricted" || $row->state == "invited" ) {
                    $_SESSION['guid'] =  $row->guid;
                    $sql = "UPDATE `accounts` SET `last_login_datetime` = ?, `last_ip` = ? WHERE `guid` = UNHEX(?)";
                    $arr = array(
                        date( 'Y-m-d H:i:s', now() ),
                        $Input->ip_address(),
                        $row->guid
                    );
                    $query = $this->db->query( $sql, $arr );
                    
                    $this->set_guid($row->guid);
                    
                    if ( $row->state == "restricted" ) {
                        return array(
                            TRUE,
                            "124"
                        );
                    }
                    
                    return array(
                        TRUE,
                        "111"
                    );
                } else {
                    if ( $row->state == "inactive" ) {
                        return array(
                            FALSE,
                            "122"
                        );
                    }
                    if ( $row->state == "suspended" ) {
                        return array(
                            FALSE,
                            "123"
                        );
                    }
                }
            }
        }
        
        $sql = "INSERT INTO `log_login_attempts` (`username`, `ip`, `created`) VALUES (?, ?, ?)";
        $arr = array(
            $username,
            $Input->ip_address(),
            date( 'Y-m-d H:i:s', now() )
        );
        $query = $this->db->query( $sql, $arr );
        
        return array(
            FALSE,
            "112"
        );
    }

    /**
     * Return True if the user was successfully logged out, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function logout ()
    {
        if ( $this->_readonly ) {
            return FALSE;
        }
        
        session_destroy();
        
        return ( ! $this->is_logged_in() );
    }
    
    // --------------------------------------------------------------------- //
    // ------------------------ VALIDATION METHODS ------------------------- //
    // -----------------------------( PUBLIC )------------------------------ //
    // --------------------------------------------------------------------- //
    
    /**
     * Return True if the password matches real password, False otherwise.
     *
     * @access public
     * @param string $hash_password            
     * @param string $check_password            
     * @return boolean
     */
    public function validate_password ( $hash_password, $check_password )
    {
        if ( $this->_readonly ) {
            return FALSE;
        }
        
        $private_key = $this->_config->item( 'encryption_key' );
        
        $salt = substr( $hash_password, strlen( $check_password ), 32 );
        $hash = hash( 'whirlpool', $private_key . substr( $check_password, 0, strlen( $check_password ) ) . $salt . substr( $check_password, strlen( $check_password ) ) );
        $hash = substr( $hash, 0, strlen( $check_password ) ) . $salt . substr( $hash, strlen( $check_password ) );
        
        return ( $hash == $hash_password );
    }

    /**
     * Return True if the user is an Admin, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_admin ()
    {
        return ( in_array( 'admin', $this->_titles ) );
    }

    /**
     * Return True if the user is a Moderator, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_moderator ()
    {
        return ( in_array( 'moderator', $this->_titles ) );
    }

    /**
     * Return True if the user is currently logged in, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_logged_in ()
    {
        if ( isset( $_SESSION['guid'] ) ) {
            
            if ( ( $_SESSION['guid'] != "" ) ) {
                $sql = "SELECT * FROM `accounts` WHERE `guid` = UNHEX(?) LIMIT 1";
                $arr = array(
                    $_SESSION['guid']
                );
                $query = $this->db->query( $sql, $arr );
                
                return ( $query->num_rows() == 1 );
            }
        }
        
        return FALSE;
    }

    /**
     * Return True if the user account is activated, False otherwise.
     *
     * @access public
     * @param string $username            
     * @return boolean
     */
    public function is_activated ()
    {
        if ( $this->_state == "" ) {
            $sql = "SELECT `state` FROM `accounts` WHERE `guid` = UNHEX(?)";
            $arr = array(
                $this->get_guid()
            );
            $rs = $this->db->query( $sql, $arr );
            
            if ( $rs->num_rows() == 1 ) {
                $row = $rs->row();
                $this->set_state( $row->state );
            }
            
            $rs->free();
        }
        
        return $this->get_state() == "active";
    }

    /**
     * Return True if the user account is suspended, False otherwise.
     *
     * @access public
     * @param string $username            
     * @return boolean
     */
    public function is_suspended ()
    {
        if ( $this->_state == "" ) {
            $sql = "SELECT `state` FROM `accounts` WHERE `guid` = UNHEX(?)";
            $arr = array(
                $this->get_guid()
            );
            $rs = $this->db->query( $sql, $arr );
            
            if ( $rs->num_rows() == 1 ) {
                $row = $rs->row();
                $this->set_state( $row->state );
            }
            
            $rs->free();
        }
        
        return $this->get_state() == "suspended";
    }

    /**
     * Return True if the user account is inactivated, False otherwise.
     *
     * @access public
     * @param string $username            
     * @return boolean
     */
    public function is_inactivated ()
    {
        if ( $this->_state == "" ) {
            $sql = "SELECT `state` FROM `accounts` WHERE `guid` = UNHEX(?)";
            $arr = array(
                $this->get_guid()
            );
            $rs = $this->db->query( $sql, $arr );
            
            if ( $rs->num_rows() == 1 ) {
                $row = $rs->row();
                $this->set_state( $row->state );
            }
            
            $rs->free();
        }
        
        return $this->get_state() == "inactive";
    }

    /**
     * Return True if the server whitelist mode is active, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_whitelist_active ()
    {
        if ( $this->_config->item( 'whitelist_mode' ) == TRUE ) {
            if ( uri_string() != "" ) {
                $segments = explode( "/", uri_string() );
                
                if ( $segments[0] == $this->_config->item( 'whitelist_redirect_clean' ) ) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            }
            
            return TRUE;
        }
        
        return FALSE;
    }

    /**
     * Return True if the user ip is whitelisted, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_whitelisted_ip ()
    {
        $Input = new CI_Input();
        $ip = $Input->ip_address();
        
        $sql = "SELECT * FROM `tmp_whitelist` WHERE `ip` = ?";
        $arr = array(
            $ip
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() >= 1 ) {
            return TRUE;
        }
        
        return FALSE;
    }

    /**
     * Return True if the user account username is whitelisted, False otherwise.
     *
     * @access public
     * @return boolean
     */
    public function is_whitelisted_username ()
    {
        $username = $this->get_username();
        
        $sql = "SELECT * FROM `tmp_whitelist` WHERE `username` = ? LIMIT 1";
        $arr = array(
            $username
        );
        $query = $this->db->query( $sql, $arr );
        
        if ( $query->num_rows() == 1 ) {
            return TRUE;
        }
        
        return FALSE;
    }

    /**
     * Return True if the username is valid, False otherwise.
     * 
     * Restrictions: 4-20 Characters, (a-z, A-Z, 0-9, _-)
     *
     * @access public
     * @param string $username            
     * @return boolean
     */
    public function is_valid_username ( $username )
    {
        if ( ! preg_match( '/^([A-Za-z0-9@_.\-]{4,20})$/', $username ) ) {
            return false;
        }
        return true;
    }

    /**
     * Return True if the name is valid, False otherwise.
     * 
     * Restrictions:
     * Maximum of 32 characters
     * Minimum of 1 character
     *
     * @access public
     * @param string $username            
     * @return boolean
     */
    public function is_valid_name ( $string )
    {
        if ( strlen( $string ) > 35 || strlen( $string ) < 1 ) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Return True if the password is valid, False otherwise.
     * 
     * Restrictions:
     * Min Length: 6
     * Max Length: 64
     * Min Required: 1 Letter + 1 Number
     * Allowed Characters: [0-9A-Za-z !@#$%(-_)^&*]
     *
     * @access public
     * @param string $password            
     * @return boolean
     */
    public function is_valid_password ( $password )
    {
        // Require at least one Lowercase: (?=.*[a-z])
        // Require at least one Uppercase: (?=.*[A-Z])
        // Require at least one Number: (?=.*\d)
        if ( ! preg_match( '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z !@#$%(-_)^&*]{6,64}$/', $password ) ) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Return True if the email is valid, False otherwise.
     *
     * @access public
     * @param string $email            
     * @return boolean
     */
    public function is_valid_email ( $email )
    {
        $this->load->helper( 'email' );
        
        return valid_email( $email );
    }

    public function is_unique_guid ( $guid )
    {
        $sql = "SELECT * FROM `accounts` 
            WHERE `guid` = UNHEX(?) 
            LIMIT 1";
        $arr = array(
            $guid
        );
        $query = $this->db->query( $sql, $arr );
        
        return ( $query->num_rows() == 0 );
    }

    public function is_unique_username ( $username )
    {
        $sql = "SELECT * FROM `accounts` 
                WHERE `username` = ? 
                LIMIT 1";
        $arr = array(
            $username
        );
        $query = $this->db->query( $sql, $arr );
        
        return ( $query->num_rows() == 0 );
    }

    public function is_unique_email ( $email )
    {
        $sql = "SELECT * FROM `accounts` 
				WHERE `email` = ?
				LIMIT 1";
        $arr = array(
            $email
        );
        $query = $this->db->query( $sql, $arr );
        
        return ( $query->num_rows() == 0 );
    }
    
    // --------------------------------------------------------------------- //
    // ------------------------ GENERATION METHODS ------------------------- //
    // -----------------------------( PUBLIC )------------------------------ //
    // --------------------------------------------------------------------- //
    
    /**
     *
     * @access public
     * @return string
     */
    public function gen_guid ()
    {
        if ( $this->_readonly ) {
            return NULL;
        }
        
        $this->load->library( 'uuid' );
        $UUID = new UUID();
        
        $rand_uuid = $UUID->generate( 4 );
        
        return str_replace( "-", "", $UUID->conv_byte2string( $rand_uuid ) );
    }

    public function gen_secure_hash ( $string )
    {
        if ( $this->_readonly ) {
            return NULL;
        }
        
        // Example Hash:
        // 430d280ae0d8cc269ca384efdd7106df1d700d4d41535d77e28c80
        // ec0e46ac97e32f712a4274f70aab5ce59326708fa33fbb4de14bc5
        // eac6bcd64d37e38f4ce2a21d43816b443cfc738e74d5c339ef8d
        $Config = new CI_Config();
        $private_key = $Config->item( 'encryption_key' );
        
        $salt = substr( hash( 'whirlpool', mcrypt_create_iv( 32 ) ), 0, 32 );
        $hash = hash( 'whirlpool', $private_key . substr( $string, 0, strlen( $string ) ) . $salt . substr( $string, strlen( $string ) ) );
        $hash = substr( $hash, 0, strlen( $string ) ) . $salt . substr( $hash, strlen( $string ) );
        
        return $hash;
    }

    public function gen_token ( $key = 'general', $guid = NULL, $email = "" )
    {
        if ( $this->_readonly ) {
            return NULL;
        }
        
        $codes = array(
            'general' => '000',
            'key' => '001',
            'invite' => '002',
            'coupon' => '003',
            'image' => '004',
            'api'   => '005'
        );
        
        if ( ! key_exists( $key, $codes ) ) {
            $key = 'general';
        }
        
        switch ( $key ) {
            case 'general':
                $token = uniqid( $codes[$key], TRUE );
                break;
            case 'key':
                if ( $email != "" ) {
                    $token = $codes[$key] . substr( $this->gen_secure_hash( $email ), 0, 125 );
                    break;
                } else {
                    return NULL;
                }
            case 'invite':
                $token = uniqid( $codes[$key] );
                break;
            case 'coupon':
                $token = uniqid( $codes[$key] );
                break;
            case 'image':
                $token = $codes[$key] . substr( str_shuffle( MD5( microtime() ) ), 0, 13 );
                break;
            case 'api':
                $token = $codes[$key] . md5(microtime() . $guid . $email);
                break;
        }
        $created_by = ( $this->is_logged_in() ) ? $this->get_guid() : NULL;
        
        if ( $key == 'coupon' ) {
            $sql = "INSERT INTO `tokens` (`token`, `type`, `guid`, `created_guid`, `created`) 
					VALUES (?, ?, UNHEX(?), UNHEX(?), ?)";
            $arr = array(
                $token,
                $key,
                $guid,
                $created_by,
                date( 'Y-m-d H:i:s', now() )
            );
            $this->db->query( $sql, $arr );
        }
        
        return $token;
    }

    public function gen_auth_code ()
    {
        if ( $this->_readonly ) {
            return NULL;
        }
        
        return rand( 10000, 99999 );
    }

    /**
     * Return the string $err_string, error codes separated by a period ("."),
     * with $err appended to the end, unchanged $err_string if $err is empty.
     * 
     * Replaces all list separaters in $err with a period ".".
     * 
     * @param string $err_string
     * @param string $err
     * @return string
     */
    public function gen_err_string ( $err_string, $err )
    {
        if ( $err_string == "" ) {
            return $err;
        } else {
            return $err_string . "." . str_replace( array(
                ",",
                ":",
                "-",
                "/",
                "_"
            ), ".", $err );
        }
    }

    /**
     * TODO: Email the User requesting a new password with instructions
     * on how to reset their account password. Return True on success,
     * false otherwise.
     * 
     * @return FALSE
     */
    public function request_new_password ()
    {
        if ( $this->_readonly ) {
            return FALSE;
        }
    }
    
    // --------------------------------------------------------------------- //
    // ----------------------------- GET METHODS --------------------------- //
    // ------------------------------( PUBLIC )----------------------------- //
    // --------------------------------------------------------------------- //
    public function get_guid ()
    {
        return ( $this->_guid == "" ) ? NULL : $this->_guid;
    }

    public function get_username ()
    {
        return ( $this->_username == "" ) ? NULL : $this->_username;
    }

    public function get_email ()
    {
        return ( $this->_email == "" ) ? NULL : $this->_email;
    }

    public function get_state ()
    {
        return ( $this->_state == "" ) ? NULL : $this->_state;
    }

    public function get_last_ip ()
    {
        return ( $this->_lastIP == "" ) ? NULL : $this->_lastIP;
    }

    public function get_last_login ()
    {
        return ( $this->_lastLogin == "" ) ? NULL : $this->_lastLogin;
    }

    public function get_join_date ()
    {
        return ( $this->_joinDate == "" ) ? NULL : $this->_joinDate;
    }
    
    public function get_security_question_1() {
        return $this->_securityQuestion1;
    }
    
    public function get_security_question_2() {
        return $this->_securityQuestion2;
    }
    
    public function get_security_question_3() {
        return $this->_securityQuestion3;
    }
    
    public function get_security_answer_1() {
        return $this->_securityAnswer1;
    }
    
    public function get_security_answer_2() {
        return $this->_securityAnswer2;
    }
    
    public function get_security_answer_3() {
        return $this->_securityAnswer3;
    }
    
    public function get_private_key() {
        return $this->_privateKey;
    }
    
    public function get_auth_code() {
        return $this->_authCode;
    }

    public function get_first_name ()
    {
        return ( $this->_firstName == "" ) ? NULL : $this->_firstName;
    }

    public function get_last_name ()
    {
        return ( $this->_lastName == "" ) ? NULL : $this->_lastName;
    }
    
    public function get_street_address() {
        return ( $this->_streetAddress == "" ) ? NULL : $this->_streetAddress;
    }
    
    public function get_optional_address() {
        return ( $this->_optionalAddress == "" ) ? NULL : $this->_optionalAddress;
    }
    
    public function get_postal_code() {
        return ( $this->_postalCode == "" ) ? NULL : $this->_postalCode;
    }
    
    public function get_city() {
        return ( $this->_city == "" ) ? NULL : $this->_city;
    }
    
    public function get_country() {
        return ( $this->_country == "" ) ? NULL : $this->_country;
    }
    
    public function get_billing_first_name ()
    {
        return ( $this->_billingFirstName == "" ) ? NULL : $this->_billingFirstName;
    }
    
    public function get_billing_last_name ()
    {
        return ( $this->_billingLastName == "" ) ? NULL : $this->_billingLastName;
    }
    
    public function get_billing_street_address() {
        return ( $this->_billingStreetAddress == "" ) ? NULL : $this->_billingStreetAddress;
    }
    
    public function get_billing_optional_address() {
        return ( $this->_billingOptionalAddress == "" ) ? NULL : $this->_billingOptionalAddress;
    }
    
    public function get_billing_postal_code() {
        return ( $this->_billingPostalCode == "" ) ? NULL : $this->_billingPostalCode;
    }
    
    public function get_billing_city() {
        return ( $this->_billingCity == "" ) ? NULL : $this->_billingCity;
    }
    
    public function get_billing_country() {
        return ( $this->_billingCountry == "" ) ? NULL : $this->_billingCountry;
    }
    
    public function get_home_phone() {
        return ( $this->_homePhone == "" ) ? NULL : $this->_homePhone;
    }
    
    public function get_mobile_phone() {
        return ( $this->_mobilePhone == "" ) ? NULL : $this->_mobilePhone;
    }
    
    public function get_work_phone() {
        return ( $this->_workPhone == "" ) ? NULL : $this->_workPhone;
    }
    
    public function get_shopping_cart_id () {
        $sql = "SELECT * FROM `shopping_carts` WHERE `guid` = UNHEX(?) LIMIT 1";
        $arr = array (
            $this->get_guid()
        );
        $query = $this->db->query($sql, $arr);
    
        if ( $query->num_rows() == 1 ) {
            $row = $query->row();
            
            return $row->id;
        }
        
        return NULL;
    }
    
    public function get_orders() {
        $sql = "SELECT `order_id` FROM `menu_orders` WHERE `guid` = UNHEX(?) AND `placed`='1' ORDER BY `datetime` DESC";
        $arr = array(
            $this->get_guid()
        );
        $query = $this->db->query($sql, $arr);
    
        if ( $query->num_rows() > 0 ) {
            return $query->result_array();
        }
        
        return array();
    }
    
    // --------------------------------------------------------------------- //
    // ----------------------------- SET Methods --------------------------- //
    // ------------------------------( PRIVATE )---------------------------- //
    // --------------------------------------------------------------------- //
    private function set_guid ( $value )
    {
        $this->_guid = ( $value == NULL ) ? "" : $value;
    }

    private function set_username ( $value )
    {
        $this->_username = ( $value == NULL ) ? "" : $value;
    }

    private function set_email ( $value )
    {
        $this->_email = ( $value == NULL ) ? "" : $value;
    }

    private function set_state ( $value )
    {
        $this->_state = ( $value == NULL ) ? "" : $value;
    }

    private function set_last_ip ( $value )
    {
        $this->_lastIP = ( $value == NULL ) ? "" : $value;
    }

    private function set_last_login ( $value )
    {
        $this->_lastLogin = ( $value == NULL ) ? "" : $value;
    }

    private function set_join_date ( $value )
    {
        $this->_joinDate = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_question_1( $value ) {
        $this->_securityQuestion1 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_question_2( $value ) {
        $this->_securityQuestion2 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_question_3( $value ) {
        $this->_securityQuestion3 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_answer_1( $value ) {
        $this->_securityAnswer1 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_answer_2( $value ) {
        $this->_securityAnswer2 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_security_answer_3( $value ) {
        $this->_securityAnswer3 = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_private_key( $value ) {
        $this->_privateKey = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_auth_code( $value ) {
        $this->_authCode = ( $value == NULL ) ? "" : $value;
    }

    private function set_first_name ( $value )
    {
        $this->_firstName = ( $value == NULL ) ? "" : $value;
    }

    private function set_last_name ( $value )
    {
        $this->_lastName = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_street_address ( $value )
    {
        $this->_streetAddress = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_optional_address ( $value )
    {
        $this->_optionalAddress = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_postal_code ( $value )
    {
        $this->_postalCode = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_city ( $value )
    {
        $this->_city = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_country ( $value )
    {
        $this->_country = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_first_name ( $value )
    {
        $this->_billingFirstName = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_last_name ( $value )
    {
        $this->_billingLastName = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_street_address ( $value )
    {
        $this->_billingStreetAddress = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_optional_address ( $value )
    {
        $this->_billingOptionalAddress = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_postal_code ( $value )
    {
        $this->_billingPostalCode = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_city ( $value )
    {
        $this->_billingCity = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_billing_country ( $value )
    {
        $this->_billingCountry = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_home_phone ( $value )
    {
        $this->_homePhone = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_mobile_phone ( $value )
    {
        $this->_mobilePhone = ( $value == NULL ) ? "" : $value;
    }
    
    private function set_work_phone ( $value )
    {
        $this->_workPhone = ( $value == NULL ) ? "" : $value;
    }
    
    
    // --------------------------------------------------------------------- //
    // ----------------------------- MOD Methods --------------------------- //
    // ------------------------------( PRIVATE )---------------------------- //
    // --------------------------------------------------------------------- //
    private function mod_username ()
    {}
}