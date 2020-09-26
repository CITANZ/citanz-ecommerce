<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ValidationResult;
use Cita\eCommerce\Model\Order;

class OrderGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{
    private static $allowed_actions = ['ItemEditForm'];

    public function ItemEditForm()
    {
        $form   =   parent::ItemEditForm();

        if ($this->record->exists()) {
            // $formActions    =   $form->Actions();
            $formActions    =   FieldList::create();
            $buttons        =   Config::inst()->get(Order::class, 'default_buttons');

            foreach ($buttons as $key => $config) {
                if (!empty($config['enabled'])) {
                    if ($key == 'refund') {
                        if ($this->record->Status == 'Payment Received' ||
                            $this->record->Status == 'Shipped' ||
                            $this->record->Status == 'Completed'
                        ) {
                            $this->create_button($key, $formActions, $config);
                        }
                    } elseif ($key == 'complete_order') {
                        if ($this->record->Status == 'Shipped') {
                            $this->create_button($key, $formActions, $config);
                        }
                    } elseif ($key == 'cheque_cleared') {
                        if ($this->record->Status == 'Invoice Pending') {
                            $this->create_button($key, $formActions, $config);
                        }
                    } elseif ($key == 'debit_cleared') {
                        if ($this->record->Status == 'Debit Pending') {
                            $this->create_button($key, $formActions, $config);
                        }
                    } elseif ($key == 'send_tracking') {
                        if ($this->record->Status == 'Payment Received') {
                            $this->create_button($key, $formActions, $config);
                        }
                    } elseif ($key == 'send_invoice') {
                        if ($this->record->Status == 'Payment Received' ||
                            $this->record->Status == 'Shipped' ||
                            $this->record->Status == 'Completed'
                        ) {
                            $this->create_button($key, $formActions, $config);
                        }
                    } else {
                        $this->create_button($key, $formActions, $config);
                    }
                }
            }
        } else {
            $formActions    =   $form->Actions();
        }

        $this->extend('UpdateActions', $formActions);

        $form->setActions($formActions);

        return $form;
    }

    private function create_button($key, &$formActions, &$config)
    {
        $button =   FormAction::create($key);
        $button->setTitle($config['label']);
        $button->addExtraClass($config['extra_class']);
        $formActions->push($button);
    }

    public function send_invoice($data, $form)
    {
        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->send_invoice(true);
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function send_tracking($data, $form)
    {
        if (empty($data['FreightID']) || empty($data['TrackingNumber'])) {
            $form->sessionMessage('Please go to <strong>Freight & tracking</strong>, choose a freight provider, and enter the freight tracking number!', 'bad', ValidationResult::CAST_HTML);
            return $this->edit(Controller::curr()->getRequest());
        }

        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->FreightID        =   $data['FreightID'];
            $this->record->TrackingNumber   =   $data['TrackingNumber'];
            $this->record->write();

            $this->record->send_tracking();

            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function cheque_cleared($data, $form)
    {
        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->cheque_cleared();
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function refund($data, $form)
    {
        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->refund();
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function debit_cleared($data, $form)
    {
        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->debit_cleared();
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function complete_order($data, $form)
    {
        $msg    =   Config::inst()->get(Order::class, 'default_buttons')[__FUNCTION__]['message'];
        $form->sessionMessage($msg, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->Status   =   'Completed';
            $this->record->write();
            return $this->edit(Controller::curr()->getRequest());
        }
    }

    public function delete($data, $form)
    {
        $this->record->delete();
        return $this->goback($data);
    }

    public function goback(&$data)
    {
        $url    =   Controller::curr()->removeAction($data['BackURL']);
        Controller::curr()->getRequest()->addHeader('X-Pjax', 'Content');
        return Controller::curr()->redirect($url, 302);
    }
}
