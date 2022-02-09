<!-- <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script> -->
<script type='text/javascript' src='https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js'></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script type='text/javascript'>



<?php 

global $woocommerce;

$cart_total = $woocommerce->cart->get_cart_contents_total();
$shipping_total = $woocommerce->cart->get_shipping_total();
$amount_total = $cart_total + $shipping_total


?>

// $(":input").inputmask();

$("#cielo-credit-number").inputmask({"mask": "9999 9999 9999 9999"});
$("#cielo-credit-card-cvc").inputmask({"mask": "999"});
$("#cielo-credit-card-expiry").inputmask({"mask": "99/9999"});

</script>

<script type='text/javascript' src='https://code.jquery.com/jquery-1.11.0.js'></script>
<script type='text/javascript' src="https://rawgit.com/RobinHerbots/jquery.inputmask/3.x/dist/jquery.inputmask.bundle.js"></script>

<div class="form-group row">
    <div class="col-12 col-md-6 mb-2"> <label>Numero do Cartão <span style="color:red;" class="required">*</span></label> <input id="cielo-credit-number" type="tel" name="cielo_credit_number"  class="form-control" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ></div>
    <div class="col-12 col-md-6"> <label>Nome no Cartão <span style="color:red;" class="required">*</span></label> <input id="cielo-card-holder-name" name="cielo_credit_holder_name"  class="form-control" placeholder="João Silva"> </div>
</div>
<div class="form-group row">
    <div class="col-12 col-md-6 mb-2"> <label>Validade (MM/AAAA) <span style="color:red;" class="required">*</span></label> <input id="cielo-credit-card-expiry" name="cielo_credit_card_expiry" class="form-control" placeholder="<?php _e( 'MM / AAAA'); ?>"></div>
    <div class="col-12 col-md-6"> <label>Código de Segurança <span style="color:red;" class="required">*</span></label> <input id="cielo-credit-card-cvc" name="cielo_credit_card_cvc" class="form-control" placeholder="&#9679;&#9679;&#9679;" > </div>
</div>
<div class=" row">
    <div class="col-12 col-md-6 mb-2">
        <label>Número de Parcelas <span style="color:red;" class="required">*</span></label>
        <select name="cielo_credit_parcelas" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
            <option selected>Quantidade de parcelas.</option>
            <option value="01">1x de R$: <?php echo number_format($amount_total,2,",",".");?></option>
            <option value="02">2x de R$: <?php echo number_format($amount_total / 2,2,",",".");?></option>
            <option value="03">3x de R$: <?php echo number_format($amount_total / 3,2,",",".");?></option>
            <option value="04">4x de R$: <?php echo number_format($amount_total / 4,2,",",".");?></option>
            <option value="05">5x de R$: <?php echo number_format($amount_total / 5,2,",",".");?></option>
            <option value="06">6x de R$: <?php echo number_format($amount_total / 6,2,",",".");?></option>
        </select>
    </div>
</div>


