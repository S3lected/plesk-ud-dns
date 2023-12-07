<?php

class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        if (!pm_Session::getClient()->isAdmin()) {
            throw new pm_Exception('Permission denied');
        }

        // Init title for all actions
        $this->view->pageTitle = 'united-domains reselling';

        // Init common tabs
        $tabs = [
            [
                'title' => 'Settings',
                'action' => 'settings'
            ]
        ];

        // Init tabs for all actions
        $this->view->tabs = $tabs;
    }

    public function indexAction()
    {
        // Default action will be formAction
        $this->_forward('settings');
    }

    public function settingsAction()
    {
        // Display simple text in view
      $this->view->descr = 'This extension adds --custom-backend for Plesk DNS in post-install.php and removed in pre-uninstall.php respectively.';

        // Init form here
        $form = new pm_Form_Simple();

        $form->addElement('checkbox', 'enabledCheckbox', array(
            'label' => 'Enabled',
            'value' => pm_Settings::get('enabledCheckbox'),
        ));

        $form->addElement('text', 'loginNameText', array(
            'label' => 'Login Name',
            'value' => pm_Settings::get('loginNameText'),
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        
        $form->addElement('password', 'passwordText', array(
            'label' => 'Password',
            'value' => '',
            'description' => 'Password: ' . pm_Settings::get('passwordText'),
            'validators' => array(
                array('StringLength', true, array(5, 255)),
            ),
        ));

        $form->addControlButtons(array(
            'cancelLink' => pm_Context::getModulesListUrl(),
        ));
       
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            // Form proccessing here
            pm_Settings::set('enabledCheckbox', $form->getValue('enabledCheckbox'));
            pm_Settings::set('loginNameText', $form->getValue('loginNameText'));
            if ($form->getValue('passwordText')) {
                pm_Settings::setEncrypted('passwordText', $form->getValue('passwordText'));
            }

        //     if (pm_Settings::get('enabledCheckbox')) {
        //     try {
        //         $script = PRODUCT_ROOT . '/bin/extension --exec ' . pm_Context::getModuleId() . ' uddns.php';
        //         $result = pm_ApiCli::call('server_dns', array('--enable-custom-backend', $script));
        //     } catch (pm_Exception $e) {
        //         echo $e->getMessage() . "\n";
        //         exit(1);
        //     }
        // } else {
        //     try {
        //         $result = pm_ApiCli::call('server_dns', array('--disable-custom-backend'));
        //     } catch (pm_Exception $e) {
        //         echo $e->getMessage() . "\n";
        //         exit(1);
        //     }
        // }

            $this->_status->addMessage('info', 'Data was successfully saved.');
            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
        }

        $this->view->form = $form;
    }

}