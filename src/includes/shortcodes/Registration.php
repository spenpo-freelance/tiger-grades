<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\Utilities\DOMHelper;
use DOMDocument;
use Spenpo\TigerGrades\API\GeneralAPI;
use Spenpo\TigerGrades\Repositories\TigerGeneralRepository;
use Spenpo\TigerGrades\Utilities\LanguageManager;
/**
 * Handles the [tigr_version] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
class RegistrationShortcode {
    private $text_translations;
    private $generalRepository;
    private $api;
    private $language_manager;
    private $plugin_domain;
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
        $this->generalRepository = new TigerGeneralRepository();
        $this->api = $this->getAPI();
        $this->language_manager = LanguageManager::getInstance();
        $this->plugin_domain = $this->language_manager->getPluginDomain();
        // Register the shortcode
        add_filter( 'wp_enqueue_scripts', [$this, 're_register_hcaptcha_script'], 20 );
        add_shortcode('tigr_registration', [$this, 'render']);
    }

    /**
     * Renders the version shortcode output.
     *
     * @param array $atts Shortcode attributes
     * @return string The rendered shortcode content
     */
    public function render($atts) {
        $lang = $this->language_manager->getCurrentLanguage();
        
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = DOMHelper::createElement($dom, 'div', 'registration-container');
        $header = DOMHelper::createElement($dom, 'h2', 'register-welcome-header', null, __('Welcome to Tiger Grades', $this->plugin_domain));
        $subHeader = DOMHelper::createElement($dom, 'h3', 'register-welcome-subheader', null, __('Which kind of account do you need', $this->plugin_domain) . '?');
        $root->appendChild($header);
        $root->appendChild($subHeader);
        $dom->appendChild($root);
        $form_control = DOMHelper::createElement($dom, 'div', 'registration-buttons', null, null, [
            'data-active' => 'user'
        ]);
        $form_control->appendChild(DOMHelper::createElement($dom, 'button', 'registration-button', null, __('Parent', $this->plugin_domain) . '/' . __('Student', $this->plugin_domain), [
            'type' => 'button',
            'name' => 'email',
            'data-form-id' => 'user',
        ]));
        $form_control->appendChild(DOMHelper::createElement($dom, 'button', 'registration-button', null, __('Teacher', $this->plugin_domain), [
            'type' => 'button',
            'name' => 'email',
            'data-form-id' => 'teacher'
        ]));
        $root->appendChild($form_control);
        
        $form_container = DOMHelper::createElement($dom, 'div', 'registration-form-container');
        
        // Use the GeneralAPI convenience method for UTF-8 compatible shortcode rendering
        $registration_forms = [
            'en' => [
                'user' => $this->generalRepository->getUserRegistrationFormId('subscriber', 'lang-en'),
                'teacher' => $this->generalRepository->getUserRegistrationFormId('teacher', 'lang-en')
            ],
            'zh' => [
                'user' => $this->generalRepository->getUserRegistrationFormId('subscriber', 'lang-zh'),
                'teacher' => $this->generalRepository->getUserRegistrationFormId('teacher', 'lang-zh')
            ]
        ];
        
        // Render and append the shortcode with proper UTF-8 support
        $shortcode = '[user_registration_form id="'.$registration_forms[$lang]['user'].'"]';
        $this->api->appendShortcodeWithUTF8Support($dom, $form_container, $shortcode);
        
        $root->appendChild($form_container);

        // Enqueue the JavaScript
        wp_enqueue_script(
            'tiger-grades-user-registration',
            plugins_url('tiger-grades/js/user-registration.js', dirname(__FILE__, 3)),
            array('jquery'),
            '1.0.3',
            true
        );
        wp_localize_script(
            'tiger-grades-user-registration',
            'tigr_ajax_object',
            array(
                'teacher_form_id' => $registration_forms[$lang]['teacher'],
                'user_form_id' => $registration_forms[$lang]['user'],
                'ajax_url' => rest_url('tiger-grades/v1/shortcode'),
                'language' => $this->language_manager->getCurrentLanguage()
            )
        );
        
        return $dom->saveHTML();
    }
    
    public function re_register_hcaptcha_script() {
        $language = $this->language_manager->getCurrentLanguage();
        wp_deregister_script('ur-recaptcha-hcaptcha');
            
        wp_register_script(
            'ur-recaptcha-hcaptcha',
            'https://hcaptcha.com/1/api.js?onload=onloadURCallback&render=explicit&hl=' . $language,
            array(),
            UR_VERSION
        );
    }

    // New protected method for better testability
    protected function getAPI() {
        return GeneralAPI::getInstance();
    }
}

// Initialize the shortcode
new RegistrationShortcode(); 