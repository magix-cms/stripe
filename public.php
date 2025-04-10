<?php
require __DIR__  . '/stripe-php/init.php';
include_once ('db.php');
class plugins_stripe_public extends plugins_stripe_db
{
    protected $template,
        $mail,
        $header,
        $data,
        $modelDomain,
        $config,
        $settings,
        $about,
        $stripe,
        $message,
        $sanitize;

    public $purchase,
        $lang,
        $custom,
        $urlStatus,
        $payment_plugin = true,
        $callback,
        $order,
        $redirect,
        $webhook;

    /**
     * plugins_hipay_public constructor.
     * @param null $t
     */
    public function __construct(frontend_model_template $t = null)
    {
        $this->template = $t instanceof frontend_model_template ? $t : new frontend_model_template();
        $this->header = new http_header();
        $this->data = new frontend_model_data($this,$this->template);
        $this->lang = $this->template->lang;
        $formClean = new form_inputEscape();
        $this->sanitize = new filter_sanitize();
        //$this->header = new component_httpUtils_header($this->template);
        $this->message = new component_core_message($this->template);
        $this->modelDomain = new frontend_model_domain($this->template);
        $this->about = new frontend_model_about($this->template);
        $formClean = new form_inputEscape();

        if (http_request::isPost('purchase')) {
            $this->purchase = $formClean->arrayClean($_POST['purchase']);
        }
        // ------ custom utilisé pour metadata
        /*if (http_request::isPost('custom')) {
            $this->custom = $formClean->arrayClean($_POST['custom']);
        }*/
        if (http_request::isGet('urlStatus')) {
            $this->urlStatus = $formClean->simpleClean($_GET['urlStatus']);
        }
        if (http_request::isGet('redirect')) {
            $this->redirect = $formClean->simpleClean($_GET['redirect']);
        }elseif (http_request::isPost('redirect')) {
            $this->redirect = $formClean->simpleClean($_POST['redirect']);
        }
        if (http_request::isGet('webhook')) {
            $this->webhook = $formClean->simpleClean($_GET['webhook']);
        }elseif (http_request::isPost('webhook')) {
            $this->webhook = $formClean->simpleClean($_POST['webhook']);
        }
        if (http_request::isPost('callback')) {
            $this->callback = $formClean->simpleClean($_POST['callback']);
        }
        /*if (http_request::isPost('order')) {
            $this->order = $formClean->simpleClean($_POST['order']);
        }*/
        $this->order = filter_rsa::tokenID();
        if (http_request::isPost('custom')) {
            $array = $_POST['custom'];
            $array['order'] = $formClean->simpleClean($this->order);
            $this->custom = $array;
        }
        //@ToDo switch to this declaration when deployed online
        $this->mail = new frontend_model_mail($this->template, 'stripe');
    }
    /**
     * Assign data to the defined variable or return the data
     * @param string $type
     * @param string|int|null $id
     * @param string $context
     * @param boolean $assign
     * @return mixed
     */
    private function getItems($type, $id = null, $context = null, $assign = true) {
        return $this->data->getItems($type, $id, $context, $assign);
    }

    /**
     * @return mixed
     */
    private function setItemsAccount(){
        return $this->getItems('root',NULL,'one',false);
    }
    /**
     * Insert data
     * @param string $type
     * @param array $params
     */
    private function add(string $type, array $params) {
        switch ($type) {
            case 'history':
                parent::insert($type, $params);
                break;
        }
    }
    /**
     * @param $setConfig
     * @return array
     */
    /*private function setUrl($setConfig){
        $baseUrl = http_url::getUrl();
        $lang = $this->template->currentLanguage();
        $setConfig['plugin'] = isset($setConfig['plugin']) ? $setConfig['plugin'] : false;
        if($setConfig['plugin']) {
            $url = $baseUrl . '/'. $lang . '/' . $setConfig['plugin'] . '/';
            return array(
                'redirectUrl' => $url . '?order',
                'webhookUrl' => $url . '?webhook'
            );
        }
    }*/

    /**
     * @param array $setConfig
     * @return string[]
     */
    private function setUrl(array $setConfig) :array{
        $baseUrl = http_url::getUrl();
        $lang = $this->lang;
        $setConfig['plugin'] = isset($setConfig['plugin']) ? $setConfig['plugin'] : false;

        if($setConfig['plugin']) {
            $url = $baseUrl . '/'. $lang . '/' . $setConfig['plugin'] . '/';
            if(isset($this->callback)){
                $callback = $baseUrl . '/'. $lang . '/' . $this->callback . '/';
            }else{
                $callback = $url;
            }

            if(isset($this->redirect)){
                $redirect = '&redirect='.$this->redirect;
            }else{
                $redirect = '';
            }
            //isset($this->redirect) ? '&redirect='.$this->redirect : '';
            // ----- todo voir pour redirectUrl
            return [
                //'webhookUrl' => $callback . '?webhook=true',
                'redirectUrl' => $url . '?order='.$setConfig['order'].$redirect
            ];
        }
        return [];
    }

    /**
     * @param $config
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createPayment($config){

        $data = $this->setItemsAccount();

        \Stripe\Stripe::setApiKey($data['endpointkey']/*$data['apikey']*/);

        // Set redirect urls
        $setUrl = $this->setUrl($config);

        ### Creating a new payment.
        $amount = $config['amount'];
        $unit_amount = (int) ($amount * 100);
        if(isset($_COOKIE['mc_cart'])){
            $this->custom['session_key_cart'] = $_COOKIE['mc_cart'];
            $this->custom['order'] = $config['order'];
        }
        /*
         * 'session_key_cart'=>$_COOKIE['mc_cart'],
                'order' =>  $config['order'],
                'email' => $this->custom["email"],
         * */
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $config['currency'],
                    'product_data' => [
                        'name' => $config['setName'],
                    ],
                    'unit_amount' => $unit_amount, // Montant en centimes
                ],
                'quantity' => $config['quantity'],
            ]],
            'mode' => 'payment',
            //"redirectUrl" => $setUrl['redirectUrl'],
            //"webhookUrl"  => $setUrl['webhookUrl'],
            'success_url' => $setUrl['redirectUrl'],
            'cancel_url' => $setUrl['redirectUrl'],
            'metadata' => $this->custom
                //'customer_address' => json_encode($customer_address), // Encodage de l'adresse en JSON
        ]);


        try {
            if(isset($config['debug']) && $config['debug'] == 'pre'){
                print '<pre>';
                print_r($session);
                print '<pre>';
            }else{
                // REDIRECT USER TO url
                header("Location: " . $session->url, true, 303);
            }
        } catch(Exception $e) {
            $logger = new debug_logger(MP_LOG_DIR);
            $logger->log('php', 'error', 'An error has occured : '.$e->getMessage(), debug_logger::LOG_MONTH);
        }
    }

    /**
     * @param $config
     * @return array|string[]
     */
    public function captureOrder($config) :array{
        ///fr/stripe/?webhook=true
        $data = $this->setItemsAccount();

        \Stripe\Stripe::setApiKey($data['endpointkey']);

        $secret_signing_key = $data['secret_signing_key'];//'whsec_';

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $secret_signing_key
            );
            /*$log = new debug_logger(MP_LOG_DIR);
            $log->tracelog('type: '.$event->type);
            if ($event->type == 'checkout.session.completed') {
                $session = $event->data->object;
                $paymentIntentId = $session->payment_intent;

                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                $log->tracelog('status: '.$paymentIntent->status);
            }*/

        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            //http_response_code(400);
            $logger = new debug_logger(MP_LOG_DIR);
            $logger->log('php', 'error', 'An error has occured : '.$e->getMessage(), debug_logger::LOG_MONTH);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            $logger = new debug_logger(MP_LOG_DIR);
            $logger->log('php', 'error', 'Webhook signature verification failed. : '.$e->getMessage(), debug_logger::LOG_MONTH);
            //error_log('⚠️  Webhook signature verification failed.');
            //http_response_code(400);
            exit();
        }
        /*
        // Récupération de la langue à partir de l'URL
        $uri = $_SERVER['REQUEST_URI'];
        $lang = substr($uri, 1, 2); // Extrait les deux premiers caractères après le premier "/"

        // Traitement de l'événement en fonction de la langue
        if ($event->type == 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            if ($lang == 'fr') {
                // ... Traiter le paiement réussi en français ...
            } else if ($lang == 'en') {
                // ... Traiter le paiement réussi en anglais ...
            }
        }*/
        // Handle the event (e.g., checkout.session.completed)
        /*if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $cart_id = $session->metadata->cart_id;
            // ... process the payment information ...
        }*/


        try {
            $getPayment = [];
            $parseEvent = json_decode($payload, true);
            $object = $parseEvent['data']['object'];
            $event_id = $parseEvent['id'];
            $order_h = $object['metadata']['order'];
            $session_key_cart = $object['metadata']['session_key_cart'];

            $log = new debug_logger(MP_LOG_DIR);
            /*$log->tracelog('start captureOrder');
            //$log->tracelog(($payload));
            $log->tracelog(($event_id));
            $log->tracelog($order_h);
            $log->tracelog('end captureOrder');*/
            /*
             * The payment is paid and isn't refunded or charged back.
             * At this point you'd probably want to start the process of delivering the product to the customer.
             */
            $price = $object['amount_total'];
            $currency = 'EUR';
            /*foreach ($event['line_items']['data'] as $item) {
                $price = $item['price_data']['unit_amount'] / 100; // Convertir en euros
                $currency = $item['price_data']['currency'];
            }*/


            $payment_method = $object['payment_method_types'][0] ?? 'test';
            $getPayment = [
                'amount' => $price,
                'method' => $payment_method,
                'metadata' => $object['metadata'],
                'status' => 'canceled',
                'currency' => $object['currency']
            ];

            /*$log->tracelog('start getPayment');
            $log->tracelog(json_encode($getPayment));
            $log->tracelog('status: '.$object['status']);
            $log->tracelog('end getPayment');*/
            if($event_id) {
                switch ($object['payment_status']) {
                    case 'paid':
                        try {
                            $session = $event->data->object;
                            $paymentIntentId = $session->payment_intent;
                            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                            if (!$paymentIntent->review) {
                                if ($paymentIntent->status == 'succeeded') {
                                    // Le paiement a réussi
                                    $this->add(
                                        'history',
                                        [
                                            'session_key_cart'=>$session_key_cart,
                                            'order_h' => $order_h,
                                            'event_h' => $event_id,
                                            'status_h' => 'paid'
                                        ]
                                    );
                                    $getPayment = [
                                        'amount' => $price,
                                        'method' => $payment_method,
                                        'metadata' => $object['metadata'],
                                        'status' => 'paid',
                                        'currency' => $object['currency']
                                    ];
                                    // ... Traiter le paiement réussi ...
                                } else {
                                    // Le paiement a échoué
                                    $this->add(
                                        'history',
                                        [
                                            'session_key_cart'=>$session_key_cart,
                                            'order_h' => $order_h,
                                            'event_h' => $event_id,
                                            'status_h' => 'canceled'
                                        ]
                                    );
                                    $getPayment = [
                                        'status' => 'canceled'
                                    ];
                                    // ... Traiter le paiement échoué ...
                                }
                            }
                        } catch (\Stripe\Exception\ApiErrorException $e) {
                            // Erreur lors de la récupération de l'objet payment_intent
                            $logger = new debug_logger(MP_LOG_DIR);
                            $logger->log('php', 'error', 'An error has occured : ' . $e->getMessage(), debug_logger::LOG_MONTH);
                        }
                        break;
                    case 'unpaid':
                    default:
                        // Le paiement a échoué ou a été annulé (canceled/failed)
                        $this->add(
                            'history',
                            [
                                'session_key_cart'=>$session_key_cart,
                                'order_h' => $order_h,
                                'event_h' => $event_id,
                                'status_h' => 'canceled'
                            ]
                        );
                        $getPayment = [
                            'status' => 'canceled'
                        ];
                        break;
                }
            }else{
                // Le paiement a échoué
                $this->add(
                    'history',
                    [
                        'session_key_cart'=>$session_key_cart,
                        'order_h' => $order_h,
                        'event_h' => $event_id,
                        'status_h' => 'canceled'
                    ]
                );
                $getPayment = [
                    'status' => 'canceled'
                ];
            }
            /*if(isset($_GET['session_id'])){
                $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
                $log = new debug_logger(MP_LOG_DIR);
                $log->tracelog('type: '.$session->cancel_url);
            }*/
           /* switch ($object['payment_status']) {
                case 'paid'://'payment_intent.succeeded':
                    // Le paiement a réussi (paid)
                    try {
                        $session = $event->data->object;
                        $paymentIntentId = $session->payment_intent;
                        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                        if (!$paymentIntent->review) {
                            if ($paymentIntent->status == 'succeeded') {
                                // Le paiement a réussi
                                $this->add(
                                    'history',
                                    [
                                        'order_h' => $order_h,
                                        'event_h' => $event_id,
                                        'status_h' => 'paid'
                                    ]
                                );
                                $getPayment = [
                                    'amount' => $price,
                                    'method' => $payment_method,
                                    'metadata' => $object['metadata'],
                                    'status' => 'paid'
                                ];
                                // ... Traiter le paiement réussi ...
                            } else {
                                // Le paiement a échoué
                                $this->add(
                                    'history',
                                    [
                                        'order_h' => $order_h,
                                        'event_h' => $event_id,
                                        'status_h' => 'canceled'
                                    ]
                                );
                                $getPayment = [
                                    'status' => 'canceled'
                                ];
                                // ... Traiter le paiement échoué ...
                            }
                        }
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        // Erreur lors de la récupération de l'objet payment_intent
                        $logger = new debug_logger(MP_LOG_DIR);
                        $logger->log('php', 'error', 'An error has occured : '.$e->getMessage(), debug_logger::LOG_MONTH);
                    }

                    break;*/
                /*case 'unpaid':
                    // Le paiement a échoué ou a été annulé (canceled/failed)
                    $this->add(
                        'history',
                        [
                            'order_h' => $order_h,
                            'event_h' => $event_id,
                            'status_h' => 'canceled'
                        ]
                    );
                    $getPayment = [
                        'status' => 'canceled'
                    ];
                    break;
                case 'charge.succeeded':
                    // Paiement direct réussi (paid)
                    $this->add(
                        'history',
                        [
                            'order_h' => $order_h,
                            'event_h' => $event_id,
                            'status_h' => 'paid'
                        ]
                    );
                    break;
                case 'charge.failed':
                    // Paiement direct échoué (canceled/failed)
                    $this->add(
                        'history',
                        [
                            'order_h' => $order_h,
                            'event_h' => $event_id,
                            'status_h' => 'failed'
                        ]
                    );
                    $getPayment = [
                        'status' => 'canceled'
                    ];
                    break;
                //default:
                // Autres événements (à gérer selon vos besoins)
                //break;*/
            //}

            if(isset($config['debug']) && $config['debug'] == 'printer'){
                $log = new debug_logger(MP_LOG_DIR);
                $log->tracelog('start payment');
                $log->tracelog(json_encode($getPayment));
                $log->tracelog('sleep');
            }else{
                return $getPayment;
            }

        }catch(Exception $e) {
            $logger = new debug_logger(MP_LOG_DIR);
            $logger->log('php', 'error', 'An error has occured : '.$e->getMessage(), debug_logger::LOG_MONTH);
        }
        return [];
    }

    /**
     * @param string $email
     * @param string $tpl
     * @param array $data
     * @param bool $file
     * @return bool|void
     */
    protected function send_email(string $email,string $tpl, array $data = [], bool $file = false) {
        if($email) {
            $this->template->configLoad();
            if(!$this->sanitize->mail($email)) {
                $this->message->json_post_response(false,'error_mail');
            }
            else {
                if($this->lang) {
                    $contact = new plugins_contact_public();
                    $sender = $contact->getContact();

                    if(!empty($sender) && !empty($email)) {
                        $allowed_hosts = array_map(function($dom) { return $dom['url_domain']; },$this->modelDomain->getValidDomains());
                        if (!isset($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
                            header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
                            exit;
                        }
                        $noreply = 'noreply@'.str_replace('www.','',$_SERVER['HTTP_HOST']);
                        $this->settings = new frontend_model_setting($this->template);
                        $from = $this->settings->getSetting('mail_sender');

                        return $this->mail->send_email($email,$tpl,$data,'',$noreply,$from['value']);
                    }
                    else {
                        $this->message->json_post_response(false,'error_plugin');
                        return false;
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getPaymentStatus() : string{
        $stripe = $this->getItems('lastHistory',['session_key_cart'=>$_COOKIE['mc_cart']],'one',false);
        $status = 'pending';

        if($stripe != null){
            $status = $stripe['status_h'];
        }
        /*$log = new debug_logger(MP_LOG_DIR);
        $log->tracelog($status);*/
        return $status;
    }
    /**
     *
     */
    public function run(){

        if(isset($_GET['order'])){
            if(isset($_COOKIE['mc_cart'])) {
                $stripe = $this->getItems('history',array('order_h'=>$_GET['order']),'one',false);

                $status = 'pending';
                if($stripe != NULL) {
                    switch ($stripe['status_h']) {
                        case 'paid':
                            $status = 'success';
                            break;
                        case 'failed':
                            $status = 'error';
                            break;
                        case 'canceled':
                        case 'expired':
                        default:
                            $status = 'canceled';
                            break;
                    }
                }
                header("location:/$this->lang/cartpay/order/?step=done_step&status=$status");
            }else{
                $stripe = $this->getItems('history',array('order_h'=>$_GET['order']),'one',false);
                $this->template->assign('stripe',$stripe);

                if(isset($this->redirect)){
                    $baseUrl = http_url::getUrl();
                    header( "Refresh: 3;URL=$baseUrl/$this->lang/$this->redirect/" );
                }
                $this->template->display('stripe/index.tpl');
            }

        }elseif(isset($this->webhook)){
            ///fr/stripe/?webhook=true
            $getPayment = $this->captureOrder(
                array(
                    'debug'=> false
                )
            );

            if(isset($getPayment['status']) && $getPayment['status'] == 'paid') {
                $result = [
                    'amount'    =>  ($getPayment['amount'] / 100),
                    'currency'  =>  $getPayment['currency']
                ];
                $metadata = (array) $getPayment['metadata'];

                foreach ($metadata as $key => $value){
                    $result[$key] = $value;
                }

                $log = new debug_logger(MP_LOG_DIR);
                $log->tracelog('start payment');
                $log->tracelog(json_encode($result));
                $log->tracelog($result['email']);
                $log->tracelog('sleep');

                if(isset($result['email'])){
                    $log->tracelog('email true');
                    //$collection['contact']['mail']
                    if(!isset($result['session_key_cart'])) {
                        $about = new frontend_model_about($this->template);
                        $collection = $about->getCompanyData();
                        $this->send_email($result['email'], 'admin', $result);
                    }
                    /*if(isset($collection['contact']['mail']) && !empty($collection['contact']['mail'])){
                        $this->send_email($collection['contact']['mail'], 'admin', $result);
                    }*/
                }else{
                    $log->tracelog('email false');
                }
            }

        }else{
            if(isset($this->purchase)) {

                $this->template->addConfigFile(
                    array(component_core_system::basePath() . '/plugins/stripe/i18n/'),
                    array('public_local_'),
                    false
                );
                $this->template->configLoad();

                $collection = $this->about->getCompanyData();
                // config data for payment
                $config = [
                    'plugin' => 'stripe',
                    'setName' => $this->template->getConfigVars('order_on') . ' ' . $collection['name'],
                    'amount' => $this->purchase['amount'],
                    'currency' => 'EUR',//$this->purchase['currency'],
                    'order' => $this->order,
                    'quantity' => $this->custom['quantity'] ?? 1,
                    'debug' => false//pre,none,printer
                ];
                //print_r($config);
                $this->createPayment($config);
            }
        }
    }
}
?>