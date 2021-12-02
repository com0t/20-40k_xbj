<?php

namespace MailOptin\Ctctv3Connect;

use MailOptin\Core\Connections\ConnectionInterface;

class Connect extends AbstractCtctv3Connect implements ConnectionInterface
{
    /**
     * @var string key of connection service. its important all connection name ends with "Connect"
     */
    public static $connectionName = 'Ctctv3Connect';

    public function __construct()
    {
        ConnectSettingsPage::get_instance();

        add_filter('mailoptin_registered_connections', array($this, 'register_connection'));
    
        add_filter('mo_optin_form_integrations_default', array($this, 'integration_customizer_settings'));
        add_action('mo_optin_integrations_controls_after', array($this, 'integration_customizer_controls'), 10, 4);

        parent::__construct();
    }

    public static function features_support()
    {
        return [
            self::OPTIN_CAMPAIGN_SUPPORT,
            self::EMAIL_CAMPAIGN_SUPPORT,
            self::OPTIN_CUSTOM_FIELD_SUPPORT
        ];
    }

    /**
     * Register Constant Contact v3 Connection.
     *
     * @param array $connections
     *
     * @return array
     */
    public function register_connection($connections)
    {
        $connections[self::$connectionName] = __('Constant Contact (v3)', 'mailoptin');

        return $connections;
    }

    /**
     * Fulfill interface contract.
     *
     * {@inheritdoc}
     */
    public function replace_placeholder_tags($content, $type = 'html')
    {
        $search = [
            '{{webversion}}'
        ];

        $replace = [
            '[[viewAsWebpage]]'
        ];

        $content = str_replace($search, $replace, $content);

        return $this->replace_footer_placeholder_tags($content);
    }

    /**
     * {@inherit_doc}
     *
     * Return array of email list
     *
     * @return mixed
     */
    public function get_email_list()
    {
        try {

            $lists = get_transient('ctctv3_get_email_list');

            if (empty($lists) || false === $lists) {

                $lists = $this->ctctv3Instance()->getContactList();

                set_transient('ctctv3_get_email_list', $lists, 10 * MINUTE_IN_SECONDS);
            }

            return $lists;

        } catch (\Exception $e) {
            self::save_optin_error_log($e->getMessage(), 'constantcontactv3');
            return [];
        }
    }

    public function get_optin_fields($list_id = '')
    {
        $custom_fields = [
            'job_title'         => __('Job Title', 'mailoptin'),
            'company_name'      => __('Company Name', 'mailoptin'),
            'birthday_month'    => __('Birthday Month', 'mailoptin'),
            'birthday_day'      => __('Birthday Day', 'mailoptin'),
            'anniversary'       => __('Anniversary', 'mailoptin'),
            'phone_number'      => __('Phone Number', 'mailoptin'),

            // Home address
            'mohma_street'      => __('Home Address Street', 'mailoptin'),
            'mohma_city'        => __('Home Address City', 'mailoptin'),
            'mohma_state'       => __('Home Address State', 'mailoptin'),
            'mohma_postal_code' => __('Home Address Postal Code', 'mailoptin'),
            'mohma_country'     => __('Home Address Country', 'mailoptin'),

            // Work address
            'mowka_street'      => __('Work Address Street', 'mailoptin'),
            'mowka_city'        => __('Work Address City', 'mailoptin'),
            'mowka_state'       => __('Work Address State', 'mailoptin'),
            'mowka_postal_code' => __('Work Address Postal Code', 'mailoptin'),
            'mowka_country'     => __('Work Address Country', 'mailoptin'),

            // Other address
            'moota_street'      => __('Other Address Street', 'mailoptin'),
            'moota_city'        => __('Other Address City', 'mailoptin'),
            'moota_state'       => __('Other Address State', 'mailoptin'),
            'moota_postal_code' => __('Other Address Postal Code', 'mailoptin'),
            'moota_country'     => __('Other Address Country', 'mailoptin'),
        ];

        try {

            $fields = get_transient('ctctv3_get_optin_fields_' . $list_id);

            if (empty($fields) || false === $fields) {

                $fields = $this->ctctv3Instance()->getContactsCustomFields();

                set_transient('ctctv3_get_optin_fields_' . $list_id, $fields, 10 * MINUTE_IN_SECONDS);
            }

            if (is_array($fields) && ! empty($fields)) {

                // custom fields (cufd)
                foreach ($fields as $field) {
                    $custom_fields['cufd_' . $field->custom_field_id] = $field->label;
                }
            }

        } catch (\Exception $e) {
            self::save_optin_error_log($e->getMessage(), 'constantcontactv3');
        }

        return $custom_fields;
    }
    
    /**
     * @param $controls
     *
     * @return array
     */
    public function integration_customizer_controls($controls)
    {
        if (defined('MAILOPTIN_DETACH_LIBSODIUM') === true) {
            $controls[] = [
                'field'       => 'chosen_select',
                'name'        => 'Ctctv3Connect_contact_tags',
                'choices'     => $this->get_tags(),
                'label'       => __('Subscriber Tags', 'mailoptin'),
                'description' => __('Select Constant Contact (v3) tags that will be assigned to contacts.', 'mailoptin')
            ];
            
            return $controls;
        }
    }
    
    /**
     * @param array $settings
     *
     * @return mixed
     */
    public function integration_customizer_settings($settings)
    {
        $settings['Ctctv3Connect_contact_tags'] = apply_filters('mailoptin_customizer_optin_campaign_Ctctv3Connect_contact_tags', []);
    
        return $settings;
    }
    
    /**
     * Fetches tags.
     *
     * @return mixed
     */
    public function get_tags()
    {
        if ( ! self::is_connected()) return [];
        
        $default = [];
        
        try {
            
            $cache_key = 'ctctv3_tags';
            
            $tag_array = get_transient($cache_key);
            
            if (empty($tag_array) || false === $tag_array) {
                $tags = $this->ctctv3Instance()->getTags();
                
                $tag_array = [];
                
                foreach ($tags as $tag) {
                    $tag_array[$tag->tag_id] = $tag->name;
                }
                
                set_transient($cache_key, $tag_array, 10 * MINUTE_IN_SECONDS);
            }
            
            return $tag_array;
            
        } catch (\Exception $e) {
            self::save_optin_error_log($e->getMessage(), 'constantcontactv3');
            
            return $default;
        }
    }

    /**
     * @param int $email_campaign_id
     * @param int $campaign_log_id
     * @param string $subject
     * @param string $content_html
     * @param string $content_text
     *
     * @return array
     * @throws \Exception
     *
     */
    public function send_newsletter($email_campaign_id, $campaign_log_id, $subject, $content_html, $content_text)
    {
        return (new SendCampaign($email_campaign_id, $campaign_log_id, $subject, $content_html, $content_text))->send();
    }

    /**
     * @param string $email
     * @param string $name
     * @param string $list_id ID of email list to add subscriber to
     * @param mixed|null $extras
     *
     * @return mixed
     */
    public function subscribe($email, $name, $list_id, $extras = null)
    {
        return (new Subscription($email, $name, $list_id, $extras))->subscribe();
    }

    /**
     * Singleton poop.
     *
     * @return Connect|null
     */
    public static function get_instance()
    {
        static $instance = null;

        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }
}