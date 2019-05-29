jQuery(document).ready(function(){
    var publishable_key = jQuery("#publishable_key").val();
    var tmode = jQuery("#payment_mode").val();
    alert(tmode);
    var amount = jQuery("#amount").val();
    var save_card = jQuery("#save_card").val();
    if( save_card == 'no') {
        save_card_val = false;
    }
    else {
        save_card_val = true;
    }
    var currency = jQuery("#currency").val();
    var billing_first_name = jQuery("#billing_first_name").val();
    var customer_user_id = jQuery("#customer_user_id").val();
    var billing_last_name = jQuery("#billing_last_name").val();
    var billing_email = jQuery("#billing_email").val();
    var billing_phone = jQuery("#billing_phone").val();
    var chg= jQuery("#chg").val();
    var config = {
        
    gateway:{
        publicKey:publishable_key,
        language:"en",
        contactInfo:true,
        supportedCurrencies:"all",
        supportedPaymentMethods: "all",
        saveCardOption:false,
        customerCards: true,
        notifications:'standard',
    labels:{
        cardNumber:"Card Number",
        expirationDate:"MM/YY",
        cvv:"CVV",
        cardHolder:"Name on Card",
        actionButton:"Pay"
    },
    style: {
        base: {
          color: '#535353',
          lineHeight: '18px',
          fontFamily: 'sans-serif',
          fontSmoothing: 'antialiased',
          fontSize: '16px',
          '::placeholder': {
            color: 'rgba(0, 0, 0, 0.26)',
            fontSize:'15px'
          }
        },
        invalid: {
          color: 'red',
          iconColor: '#fa755a '
        }
    }
  },
  customer:{
    id: '',
    first_name: billing_first_name,
    middle_name: "Middle Name",
    last_name: billing_last_name,
    email: billing_email,
    phone: {
        country_code: "965",
        number: billing_phone
    }
  },
order:{
    amount: amount,
    currency:currency,
        items:[{
            id:1,
            name:'item1',
            description: 'item1 desc',
            quantity:'x1',
            amount_per_unit:'KD00.000',
        discount: {
            type: 'P',
            value: '10%'
        },
    total_amount: 'KD000.000'
    },
    {
      id:2,
      name:'item2',
      description: 'item2 desc',
      quantity:'x2',
      amount_per_unit:'KD00.000',
      discount: {
        type: 'P',
        value: '10%'
      },
      total_amount: 'KD000.000'
    },
    {
      id:3,
      name:'item3',
      description: 'item3 desc',
      quantity:'x1',
      amount_per_unit:'KD00.000',
      discount: {
        type: 'P',
        value: '10%'
      },
      total_amount: 'KD000.000'
    }],
    shipping:null,
    taxes: null
  },
transaction:{
   mode: tmode
}
}
if (tmode=='authorize') {
    var object_tmode = {
            auto:true,
            type:"VOID",
            time:"100",
            saveCard: save_card_val,
            threeDSecure: false,
            description: "Test Description",
            statement_descriptor: "Sample",
            reference:{
                transaction: "txn_0001",
                order: "ord_0001"
            },
            metadata:{},
            receipt:{
                email: false,
                sms: true
            },
        redirect: jQuery('#tap_end_url').val(),
        post: jQuery('#tap_end_url').val(),
        };
        config.transaction['authorize']=object_tmode
}
if (tmode=='charge') {
    var object_tmode = {
            saveCard: save_card_val,
            threeDSecure: true,
            description: "Test Description",
            statement_descriptor: "Sample",
            reference:{
                transaction: "txn_0001",
                order: "ord_0001"
            },
            metadata:{},
            receipt:{
                email: false,
                sms: true
            },
        redirect: jQuery('#tap_end_url').val(),
        post: jQuery('#tap_end_url').val(),
    };
        config.transaction['charge']=object_tmode
}
//console.log(object_tmode);
console.log(config);
goSell.config(config);
});

jQuery(function($){
    var checkout_form = jQuery( 'form.woocommerce-checkout' );
          checkout_form.on( 'checkout_place_order', chg);
        });

