<?php defined( 'ABSPATH' ) || exit;

function init_GS_cielo_debit_class(){

    class WC_Gateway_cielodebit extends WC_Payment_Gateway {

       public function __construct(){

        $this->id = 'gs-cielo-api-debit';
        $this->has_fields = true;
        $this->method_title = __('Cielo - Debito');
        $this->method_description = __('Integração de Via API Cielo');

        $this->init_form_fields();
        $this->init_settings();

        $this->title 		 	= $this->get_option('title');
        $this->description 	 	= $this->get_option('description');
        $this->sandbox 	 	 	= ('yes' === $this->get_option('sandbox'));
		$this->client_id 	 	= $this->sandbox ? $this->get_option('sandbox_merchant_id') 		: $this->get_option('merchant_id');
        $this->client_key 	    = $this->sandbox ? $this->get_option('sandbox_merchant_key') 	    : $this->get_option('merchant_key');
        $this->api_url          = $this->sandbox ? 'https://apisandbox.cieloecommerce.cielo.com.br/'         : 'https://api.cieloecommerce.cielo.com.br/';

        add_action ('woocommerce_update_options_payment_gateways_'. $this-> id, array ($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array( $this, 'order_summary_preview' ) );
        
       }

       public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Habilitar'),
                    'label'       => __('Habilita ou Desabilita a forma de pagamento'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => __('Nome que aparece pro cliente no finalizar pedido'),
                    'type'        => 'text',
                    'description' => __('Nome que aparece pro cliente no finalizar pedido'),
                    'default'     => __('Pagamento Cartão de Débito'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Descrição'),
                    'type'        => 'textarea',
                    'description' => __('Informação extra mostrada na seleção da forma de pagamento'),
                    'default'     => __('Segurança via Cielo'),
                ),
                'sandbox' => array(
                    'title'       => __('Sandbox'),
                    'label'       => __('Marque para usar versao de testes api Cielo (SANDBOX)'),
                    'type'        => 'checkbox',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'sandbox_merchant_id' => array(
                    'title'       => __('SANDBOX: MerchantId'),
                    'type'        => 'text',
                    'class'	      => 'sandbox'
                ),
                'sandbox_merchant_key' => array(
                    'title'       => __('SANDBOX: MerchantKey'),
                    'type'        => 'text',
                    'class'	      => 'sandbox'
                ),
                'merchant_id' => array(
                    'title'       => __('MerchantId'),
                    'type'        => 'text',
                    'class'	      => 'production'		
                ),
                'merchant_key' => array(
                    'title'       => __('MerchantKey'),
                    'type'        => 'text',
                    'class'	      => 'production'		
                ),
            );
        }

        //CAMPO EMBAIXO DA OPÇÃO DE PAGAMENTO PARA DESCRIÇÕES.
        function payment_fields()
        {
            
            if(!empty($this->description)) {
                echo wpautop(trim(sanitize_text_field($this->description)));
                
            }
            if (!$this->sandbox){
                require __DIR__ . '/view/front-debit.php';
            }
            if ($this->sandbox){ 
                echo wpautop(__('</br><p>Modo de testes (SANDBOX). Pagamentos Gerados não validos.</p>'));
                require __DIR__ . '/view/front-debit.php';

            }
            
        }

        // FUNÇÃO PADRAO DO WOOCOMMERCE PARA PROCESSO DO PAGAMENTO
        function process_payment( $order_id ) {

            global $woocommerce;
            $order = new WC_Order( $order_id );

            $numberDebitCard = sanitize_text_field($_POST['cielo_debit_number']);
            $brendNumber = sanitize_text_field($_POST['cielo_debit_number']);
            $holderDebitCard = sanitize_text_field($_POST['cielo_debit_holder_name']);
            $expirationDateDebit   = sanitize_text_field($_POST['cielo_debit_expiry']);
            $securityCodeDebit  = sanitize_text_field($_POST['cielo_debit_cvc']);
            // var_dump($numberCreditCard);

            //IMPORTA COMPOSER
            require __DIR__ . '/vendor/autoload.php';

            /* Verifica Qual Bandeira da Cartão */

            $numbBrand = preg_replace("/[^0-9]/", "", $numbBrand); //remove caracteres não numéricos
            if(strlen($numbBrand) < 13 || strlen($numbBrand) > 19){
            }
                // return false;
            //O BIN do Elo é relativamente grande, por isso a separação em outra variável
            $elo_bin = implode('|', array('636368','438935','504175','451416','636297','506699','509048','509067','509049','509069','509050','09074','509068','509040','509045','509051','509046','509066','509047','509042','509052','509043','509064','509040'));
            $expressoes = array(
                "elo"           => "/^(('.$elo_bin.'[0-9]{10})|(36297[0-9]{11})|(5067|4576|4011[0-9]{12}))/",
                "discover"      => '/^((6011[0-9]{12})|(622[0-9]{13})|(64|65[0-9]{14}))/',
                "diners"        => '/^((301|305[0-9]{11,13})|(36|38[0-9]{12,14}))/',
                "amex"          => '/^((34|37[0-9]{13}))/',
                "hipercard"     => '/^((38|60[0-9]{11,14,17}))/',
                "aura"          => '/^((50[0-9]{14}))/',
                "jcb"           => '/^((35[0-9]{14}))/',
                "master"        => '/^((5[0-9]{15}))/',
                "visa"          => '/^((4[0-9]{12,15}))/'
            );
            foreach($expressoes as $bandeira => $expressao){
                if(preg_match($expressao, $numbBrand)){
                    $brand = $bandeira;
                }
            }


            /* Criando um client (Ele é a classe que faz as requisições ) API*/
            /* base_uri URL BASE */
            $client = new \GuzzleHttp\Client([
                'base_uri' => $this->api_url, 
                'headers' => [       
                    'Content-Type' => 'application/json',
                    'MerchantId'   => $this->client_id,
                    'MerchantKey'  => $this->client_key
                    
                ]
            ]);

            //VERIFICA O TIPO DE DOCUMENTO "CPF OU CNPJ"
            $document_type = $order->billing_persontype;
            if ( $document_type == 1 ){
                $document_type = "CPF";
            }else{
                $document_type = "CNPJ";
            }

            // VERIFICA E RETIRA " - " do CEP
            $number_postal_code = preg_replace('/[^0-9]/', '', $order->get_billing_postcode());

            $number_amount = preg_replace('/[^0-9]/', '', $order->get_total());

            //CONCATENAÇÃO NOME COMPLETO
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
            $name_full = $first_name . " " . $last_name;

            $test_order = new WC_Order($order_id);
            $test_order_key = $test_order->order_key;

            /* AQUI UM ARRAY, CONTEUDO PASSADO PRA API */
            $body = [
                'MerchantOrderId'=>(string) $order_id,
                'Customer' => [
                    'Name'   => $name_full,
                    'Email'  => $order->get_billing_email(),
                    'Address' => [
                        'Street'       => $order->get_billing_address_1(),
                        'Number'       => $order->billing_number,
                        'Complement'   => $order->get_billing_address_2(),
                        'ZipCode'      => $number_postal_code,
                        'City'         => $order->get_billing_city(),
                        'State'        => $order->get_billing_state(),
                        'Country'      => "BRA"
                    ]
                ],
                'Payment' => [
                    'Type'             =>'DebitCard',
                    'Authenticate' => true,
                    'Amount'           => $number_amount,
                    'ReturnUrl' => 'https://localhost/essystem/finalizar-compra/order-received/' . $order_id . '/?key=' . $test_order_key,
                    'SoftDescriptor'   => get_bloginfo('name'),
                    'DebitCard' => [
                        'CardNumber'     => $numberDebitCard,
                        'Holder'         => $holderDebitCard,
                        'ExpirationDate' => $expirationDateDebit,
                        'SecurityCode'   => $securityCodeDebit,
                        'Brand'          => $brand
                    ],
                    'IsCryptoCurrencyNegotiation' => false
                ]
                
            ];

            /*  TIPO METODO E NOME DO METODO API*/
            $response = $client->post('/1/sales', [
                \GuzzleHttp\RequestOptions::JSON => $body
            ]);

            //RETORNO DA COMUNICAÇÃO
            $corpoRetorno = json_decode($response->getBody()->getContents());
            var_dump($corpoRetorno);
            
            $res = $corpoRetorno->Payment->ReturnMessage;
            $paymentId = $corpoRetorno->Payment->PaymentId;
            $authenticationUrl = $corpoRetorno->Payment->AuthenticationUrl;
            $linkRes = array_column($corpoRetorno->Payment->Links, 'Href');
            var_dump($authenticationUrl);
            
            //ADICIONA O CONTEUDO DA ARRAY EM UM METADATA PARA SER LIDO NA PROXIMA PAGINA
            $order->add_meta_data('ID_Pagamento', $paymentId, true);
            $order->add_meta_data('GET', $linkRes[0], true);
            $order->add_meta_data('Link_validade', $authenticationUrl, true);
            // $order->add_meta_data('Void', $linkRes[2], true);

             
            // VERIFICA SE O BOLETO FOI GERADO COM SUCESSO E RETONA PEDIDO CONCLUIDO P/ WOOCOMMERCE.
            // if($res == 'Operation SuccessfulOFF' || 'Transacao autorizada') {
            //     // $order->update_status('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
            //     $order->payment_complete('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
            //     // wc_reduce_stock_levels($order); UTILIZAR SE PRECISA QUE BAIXA O ESTOQUE
            //     // $order->add_order_note(__('Pedido recebido aguardando pagamento do boleto'));
            //     $woocommerce->cart->empty_cart();
                
    
            //     $status['result'] = 'success';
            //     $status['redirect'] =  $this->get_return_url( $order );
            // }
    
            // return $status;

            if(!$authenticationUrl == ''){
                
                    $status['result'] = 'success';
                    $status['redirect'] =  $this->get_return_url( $order );
                    $order->payment_complete('Processing', __( 'Pagamento Aprovado Pela Operadora.'));

            }
            return $status;
            // if($res == 'Operation Successful' or $res == 'Transacao autorizada') {
            //         // $order->update_status('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
            //         // $order->payment_complete('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
            //         // wc_reduce_stock_levels($order); UTILIZAR SE PRECISA QUE BAIXA O ESTOQUE
            //         // $order->add_order_note(__('Pedido recebido aguardando pagamento do boleto'));
            //         $woocommerce->cart->empty_cart();
                    
        
            //         $status['result'] = 'success';
            //         $status['redirect'] =  $this->get_return_url( $order );
            //     }
        
            //     return $status;

        }
        

        // EXIBE BOLETO DEPOIS DE GERADO NA PROXIMA PAGINA
        function order_summary_preview( $order_id ) {
		
            
            $order = wc_get_order( $order_id );
            $verifid = $order->get_meta('Link_validade');

            //EXIBE IFRAME E BOLETO PARA DOWNLOAD.
            $html = '<p></p>';
            $html = '<p>' . __( 'Por favor, pague o boleto para que sua compra seja aprovada.', 'woo-cielo-boleto' ) .' <a href="">Baixar Boleto em PDF</a></p>';
            $html .= '<p><iframe src="'. $verifid .'" style="width:100%; height:1000px;border: solid 1px #eee;"></iframe></p>';
             
            echo '<p>' . $html . '</p>';
            		
        }
        
        
    }
}

// INFORMA AO WOOCOMMERCE QUE EXISTE UMA NOVA FUNÇÃO
function GS_cielo_debit( $methods ) {
    $methods[] = 'WC_Gateway_cielodebit'; 
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'GS_cielo_debit' );

