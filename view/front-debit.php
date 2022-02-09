
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
<script type='text/javascript' src='https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js'></script>

<script type='text/javascript'>

// $(":input").inputmask();

$("#cielo-debit-number").inputmask({"mask": "9999 9999 9999 9999"});
$("#cielo_debit_card_cvc").inputmask({"mask": "999"});
$("#cielo_debit_card_expiry").inputmask({"mask": "99/9999"});

</script>

<script type='text/javascript' src='https://code.jquery.com/jquery-1.11.0.js'></script>
<script type='text/javascript' src="https://rawgit.com/RobinHerbots/jquery.inputmask/3.x/dist/jquery.inputmask.bundle.js"></script>

<div class="form-group row">
    <div class="col-12 col-md-6 mb-2"> <label>Numero do Cartão DEB<span style="color:red;" class="required">*</span></label> <input id="cielo-debit-number" type="tel" name="cielo_debit_number"  class="form-control" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ></div>
    <div class="col-12 col-md-6"> <label>Nome no Cartão <span style="color:red;" class="required">*</span></label> <input id="cielo_debit_holder_name" name="cielo_debit_holder_name"  class="form-control" placeholder="João Silva"> </div>
</div>
<div class="form-group row">
    <div class="col-12 col-md-6 mb-2"> <label>Validade (MM/AAAA) <span style="color:red;" class="required">*</span></label> <input id="cielo_debit_card_expiry" name="cielo_debit_expiry" class="form-control" placeholder="<?php _e( 'MM / AAAA'); ?>"></div>
    <div class="col-12 col-md-6"> <label>Código de Segurança <span style="color:red;" class="required">*</span></label> <input id="cielo_debit_card_cvc" name="cielo_debit_cvc" class="form-control" placeholder="&#9679;&#9679;&#9679;" > </div>
</div>


