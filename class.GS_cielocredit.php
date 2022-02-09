<?php defined( 'ABSPATH' ) || exit;

function init_GS_cielo_credit_class(){

    class WC_Gateway_cielocredit extends WC_Payment_Gateway {

       public function __construct(){

        $this->id = 'cielo-credit';
        $this->has_fields = true;
        $this->method_title = __('Cielo - Cartão de Crédito');
        $this->method_description = __('Integração de Via API Cielo');

        $this->init_form_fields();
        $this->init_settings();

        $this->title 		 	= $this->get_option('title');
        $this->description 	 	= $this->get_option('description');
        $this->sandbox 	 	 	= ('yes' === $this->get_option('sandbox'));
		$this->client_id 	 	= $this->sandbox ? $this->get_option('sandbox_merchant_id') 		: $this->get_option('merchant_id');
        $this->client_key 	    = $this->sandbox ? $this->get_option('sandbox_merchant_key') 	    : $this->get_option('merchant_key');
        $this->api_url          = $this->sandbox ? 'https://apisandbox.cieloecommerce.cielo.com.br/'         : 'https://api.cieloecommerce.cielo.com.br/';
        $this->credit_installments	    = $this->get_option('credit_installments');
        $this->min_amount	    = $this->get_option('min_amount');


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
                    'default'     => __('Pagamento Cartão de Crédito'),
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
                'credit_installments' => array(
                    'title'       => __('Até quantas vezes Parcelar?'),
                    'type'        => 'select',
                    'options'  => array(
                        '1'    => __('1', 'woocommerce'),
                        '2'   => __('2', 'woocommerce'),
                    ),
                    'default'  => 'on-hold',
                ),
                'min_amount' => array(
                    'title'       => __('Valor Minimo Por transação ?'),
                    'type'        => 'text',
                    'default'     => __('30'),		
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
                require __DIR__ . '/view/front-credit.php';
            }
            if ($this->sandbox){ 
                echo wpautop(__('</br><p>Modo de testes (SANDBOX). Pagamentos Gerados não validos.</p>'));
                require __DIR__ . '/view/front-credit.php';

            }
            
        }

        //verificações dos campos
        function validate_fields(){

            global $woocommerce;

            $cart_total = $woocommerce->cart->get_cart_contents_total();
            $shipping_total = $woocommerce->cart->get_shipping_total();
            $amount_total = $cart_total + $shipping_total;

 
            $numberCreditCard = sanitize_text_field($_POST['cielo_credit_number']);
            $securityCode   = sanitize_text_field($_POST['cielo_credit_card_cvc']);

            //verifica numero cartao
            function luhn_check($number) {

                // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
                $number=preg_replace('/\D/', '', $number);
              
                // Set the string length and parity
                $number_length=strlen($number);
                $parity=$number_length % 2;
              
                // Loop through each digit and do the maths
                $total=0;
                for ($i=0; $i<$number_length; $i++) {
                  $digit=$number[$i];
                  // Multiply alternate digits by two
                  if ($i % 2 == $parity) {
                    $digit*=2;
                    // If the sum is two digits, add them together (in effect)
                    if ($digit > 9) {
                      $digit-=9;
                    }
                  }
                  // Total up the digits
                  $total+=$digit;
                }
              
                // If the total mod 10 equals 0, the number is valid
                return ($total % 10 == 0) ? TRUE : FALSE;   
            }

            $data_inicial = date('m/Y');
            $data_final = $_POST['cielo_credit_card_expiry'];

            $data_i = explode('/', $data_inicial);
            $data_f = explode('/', $data_final);

            $data_a = $data_i['2'].$data_i['1'].$data_i['0'];
            $data_b = $data_f['2'].$data_f['1'].$data_f['0'];
            

            if( empty( $numberCreditCard )) {
                wc_add_notice(  'Digite Numero do seu cartão!', 'error' );
                return false;
            }

            if( luhn_check($numberCreditCard) == false || $numberCreditCard < 1) {
                wc_add_notice(  'Numero de cartão invalido!', 'error' );
                return false;
            }

            if( empty( $_POST[ 'cielo_credit_holder_name' ]) ) {
                wc_add_notice(  'Digite o Nome impresso do seu cartão!', 'error' );
                return false;
            }

            if( empty( $_POST[ 'cielo_credit_card_expiry' ]) ) {
                wc_add_notice(  'Digite a data de validade do seu cartão!', 'error' );
                return false;
            }
            if( $data_a > $data_b ) {
                wc_add_notice(  'Validade do seu cartão invalida!', 'error' );
                return false;
            }
            
            if( $securityCode < 3 || !ctype_digit($securityCode) ) {
                wc_add_notice(  'Verifique Código de segurança do seu cartão!', 'error' );
                return false;
            }

            if( empty( $_POST[ 'cielo_credit_card_cvc' ])) {
                wc_add_notice(  'Digite Código de segurança do seu cartão!', 'error' );
                return false;
            }

            if( empty( $_POST[ 'cielo_credit_parcelas' ]) || $_POST[ 'cielo_credit_parcelas' ] == 'Quantidade de parcelas.' ) {
                wc_add_notice(  'Selecione a quantidade parcelas!', 'error' );
                return false;
            }
            if( $amount_total <= $this->min_amount ) {
                wc_add_notice(  'Valor Minimo por Compra : R$ ' . $this->min_amount, 'error' );
                return false;
            }
            
            return true;
         
        }

        // FUNÇÃO PADRAO DO WOOCOMMERCE PARA PROCESSO DO PAGAMENTO
        function process_payment( $order_id ) {

            global $woocommerce;
            $order = new WC_Order( $order_id );

            $numberCreditCard = sanitize_text_field($_POST['cielo_credit_number']);
            $numbBrand = sanitize_text_field($_POST['cielo_credit_number']);
            $holderCreditCard = sanitize_text_field($_POST['cielo_credit_holder_name']);
            $expirationDate   = sanitize_text_field($_POST['cielo_credit_card_expiry']);
            $securityCode   = sanitize_text_field($_POST['cielo_credit_card_cvc']);
            $installments   = sanitize_text_field($_POST['cielo_credit_parcelas']);

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
                break;
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
                    'Type'             =>'CreditCard',
                    'Amount'           => $number_amount,
                    'Installments'     => intval($installments),       /* Numero de parcelas */
                    'SoftDescriptor'   => get_bloginfo('name'),
                    'CreditCard' => [
                        'CardNumber'     => preg_replace("/[^0-9]/", "", $numberCreditCard),
                        'Holder'         => $holderCreditCard,
                        'ExpirationDate' => $expirationDate,
                        'SecurityCode'   => $securityCode,
                        'Brand'          => $brand
                    ],
                    'IsCryptoCurrencyNegotiation' => false
                ]
                
            ];
            // var_dump($body);

            /*  TIPO METODO E NOME DO METODO API*/
            $response = $client->post('/1/sales', [
                \GuzzleHttp\RequestOptions::JSON => $body
            ]);

            //RETORNO DA COMUNICAÇÃO
            $corpoRetorno = json_decode($response->getBody()->getContents());
            $statusCode = json_decode($response->getStatusCode());
            // var_dump($corpoRetorno);
            
            $res = $corpoRetorno->Payment->ReturnMessage;
            $paymentId = $corpoRetorno->Payment->PaymentId;
            $linkRes = array_column($corpoRetorno->Payment->Links, 'Href');
            $qty_parcelas = $corpoRetorno->Payment->Installments;
            
            //ADICIONA O CONTEUDO DA ARRAY EM UM METADATA PARA SER LIDO NA PROXIMA PAGINA
            $order->add_meta_data('ID_Pagamento', $paymentId, true);
            $order->add_meta_data('GET', $linkRes[0], true);
            $order->add_meta_data('Capture', $linkRes[1], true);
            $order->add_meta_data('Void', $linkRes[2], true);
            $order->add_meta_data('Status_Pedido', $res, true);
            $order->add_meta_data('Qty_Parcelas', $qty_parcelas, true);

             
            // VERIFICA SE O BOLETO FOI GERADO COM SUCESSO E RETONA PEDIDO CONCLUIDO P/ WOOCOMMERCE.  || 'Transacao autorizadaOFF'
            if($res == 'Operation Successful' or $res == 'Transacao autorizada') {
                // $order->update_status('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
                $order->payment_complete('Processing', __( 'Pagamento Aprovado Pela Operadora.'));
                // wc_reduce_stock_levels($order); Reduzir estoque
                // $order->add_order_note(__('Pedido recebido aguardando pagamento do boleto'));
                $woocommerce->cart->empty_cart();
                
    
                $status['result'] = 'success';
                $status['redirect'] =  $this->get_return_url( $order );
            }
            else{
                wc_add_notice( __('Erro no Pagamento, Verifique dados do cartão ou Valor da parcela, (Minimo R$ 5,00).', 'woothemes') . $error_message, 'error' );
                return;

            }
    
            return $status;

        }
        

        // EXIBE BOLETO DEPOIS DE GERADO NA PROXIMA PAGINA
        function order_summary_preview( $order_id ) {
		
    
            $order = wc_get_order( $order_id );

            

            //EXIBE IFRAME E BOLETO PARA DOWNLOAD.
            $html = '<p></p>';
            $html = '<p>' . __( 'Pagamento Aprovado Pela Operadora, Estamos Processando seu pedidos e logo estaremos Enviando!' ) .' <a class="font-weight-bold" href="'. esc_url( home_url( '/minha-conta/orders' ) ) .'"> Acompanhe seu pedido AQUI!</a></p>';

            echo '<p>' . $html . '</p>';
            		
        }
        
        
    }
}

// INFORMA AO WOOCOMMERCE QUE EXISTE UMA NOVA FUNÇÃO
function GS_cielo_credit( $methods ) {
    $methods[] = 'WC_Gateway_cielocredit'; 
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'GS_cielo_credit' );

