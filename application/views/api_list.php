<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <title>GC Gateway Function List</title>
  <style type="text/css">
    /* Add custom styles here */

body {
  background-color: #f4f4f4;
}

.container {
  background-color: #fff;
  padding: 20px;
  border-radius: 5px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  margin-top: 50px;
}

.list-group-item {
  border: none;
  border-top: 1px solid #dee2e6;
}

.list-group-item:last-child {
  border-bottom: 1px solid #dee2e6;
}

.list-group-item:hover {
  background-color: #f8f9fa;
  cursor: pointer;
}

.list-group-item h5 {
  color: #343a40;
}

.list-group-item p {
  color: #6c757d;
}

  </style>
</head>
<body>

<div class="container">
  <h1>API Gateway List</h1>
  <ul id="api-list" class="list-group">
    <li class="list-group-item list-group-item-primary " data-id="balanceInq" data-name="Balance Inquiry">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Balance Inquiry </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/balance</p>

      
      </li>


       <li class="list-group-item list-group-item-warning " data-id="redem" data-name="Redemption">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Redemption </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/redeem</p>

      
      </li>


       <li class="list-group-item list-group-item-success " data-id="voidRedem" data-name="Void Redemption">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Void Redemption </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/void</p>

      
      </li>


       <li class="list-group-item list-group-item-info " data-id="reversalRedem" data-name="Reversal Redemption">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Reversal Redemption </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/reverse</p>

      
      </li>


       <li class="list-group-item list-group-item-danger " data-id="voidReversal" data-name="Void Reversal">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Void Reversal </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/reversevoid</p>

      
      </li>

      <li class="list-group-item list-group-item-dark " data-id="settlement" data-name="Settlement">
        <h5 class="mb-1"><span class="badge badge-success">POST</span> Settlement </h5>
        <p class="mb-1"><span class="badge badge-dark">Endpoint</span> /giftcard-apigateway-pinelabs/settlement</p>

      
      </li>

      
  </ul>
</div>
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog " role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle"><span class="badge badge-success">POST</span> Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <div class="card" >
            <div class="card-body">
              <h6 class="card-title"> </h6>
              <h7 class="card-subtitle mb-2 text-muted">Request</h7>
              <p class="card-text"><pre><code id="request"></code></pre></p>

                <h7 class="card-subtitle mb-2 text-muted">Response</h7>
           <p class="card-text"><pre><code id="response"></code></pre></p>
          
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script type="text/javascript">
  $(document).ready(function() {

   $('.list-group-item').on('click', function() {
    var $this = $(this);
    var $alias = $this.data('alias');
   
   

    $('.active').removeClass('active');
    $this.toggleClass('active');




    $("#modalTitle").html("<span class='badge badge-success'>POST</span> "+$this.attr("data-name"));

    if ($this.attr("data-id") == 'balanceInq') {

    $(".card-title").html("<p class='mb-1'><span class='badge badge-dark'>Endpoint</span> /giftcard-apigateway-pinelabs/balance</p> ")
      var requestData = {
    "ProcessingCode":"310000",
    "Stan":"0200",
    "PosCode":"0000000",
    "NII":"0210",
    "PosCondCode":"11111",
    "Track2Data":"9141001000003327",
    "TerminalID":"32534564654654",
    "MerchantID":"000022223333444",
    "pinKey":"152879"
};

     var responseData = {
    "02^PAN": "9141001000003327",
    "03^ProcessingCode": "310000",
    "04^TransactionAmount": 0,
    "11^Stan": "0200",
    "12^Date": "2023-08-14",
    "13^Time": "15:33PM",
    "24^NII": "0210",
    "37^ReferenceNumber": 707,
    "38^ApprovalCode": "64251121",
    "39^ResponseCode": 0,
    "41^TerminalID": "32534564654654",
    "42^MerchantID": "000022223333444",
    "63^Balance": 147
};

      var textedRequestJson = JSON.stringify(requestData, undefined, 2);
      var textedResponseJson = JSON.stringify(responseData, undefined, 2);

      $('#request').text(textedRequestJson);
      $('#response').text(textedResponseJson);

  
    }else{
        $('#request').text('');
      $('#response').text('');
       $(".card-title").html('')
    }


    $('#exampleModalCenter').modal('show');
    // Pass clicked link element to another function
   // myfunction($this, $alias)
})


  });
</script>
</body>
</html>
