<?php
/*
 * Response Code and Message
 */

$lang['0'] = 'Transaction Successful'; //Transactions is Successful
$lang['10003'] = 'Card Program not applicable'; //This error indicates that the Card Program entered is not applicable for the transaction to take place. 
$lang['10008'] = 'Authorization Failed'; //This error indicates that some of the mandatory input parameters are wrong or user performing the transaction does not have required permission to perform the transaction.
$lang['10009'] = 'Security check failed'; //This error indicates that the user performing the transaction does not have required permission to perform the transaction 
$lang['10010'] = 'Balance is insufficient '; //This error indicates that the account balance is not sufficient for the transaction to take place. 
$lang['10018'] = 'Transaction failed'; //This error indicates that the transaction failed due to some critical server error for the current set of input requests.
$lang['10023'] = 'No cards available  '; //This error indicates that cards are not available for the transaction to take place. 
$lang['10042'] = 'Insufficient balance'; //This error indicates that the balance is not sufficient in the account for the transaction to take place. 
$lang['10059'] = 'Amount Incorrect '; //This error indicates that the amount entered for the transaction is not correct. 
$lang['10064'] = 'Invalid batch'; //This error occurs when the same TerminalID is used to initialize multiple instances of application.
$lang['10103'] = 'Card Program Group does not exist '; //The Card Program Group passed in the transaction does not exist. 
$lang['10120'] = 'Inactive pos'; //The POS used for the transaction is inactive.
$lang['10121'] = 'Invalid TerminalId'; //The Terminal Id passed in the transaction is invalid.
$lang['10122'] = 'POS does not exist'; //The POS used for the transaction does not exist. 
$lang['10123'] = 'Invalid username or password'; //The user credentials passed in the transaction are invalid
$lang['10124'] = 'Invalid ForwardingEntityID or ForwardingEntityPwd'; //The Forwarding Entity credentials passed in the transaction are invalid. 
$lang['10125'] = 'POSTypeAuth failed.'; //This error indicates that the POS type authentication failed.  
$lang['10126'] = 'Merchant Outlet Auth failed.'; //This error indicates that the merchant outlet authentication failed. 
$lang['10130'] = 'Username not provided.'; //Username is not provided for the transaction. 
$lang['10131'] = 'User does not exist.'; //The username passed in the transaction does not exist. 
$lang['10171'] = 'TransactionTypeId is not provided.'; //Transaction Type Id is not provided for the transaction. 
$lang['10172'] = 'TransactionTypeId is incorrect.'; //Transaction Type Id provided for the transaction is not correct. 
$lang['10173'] = 'TransactionId is not provided.'; //Transaction Id is not provided for the transaction. 
$lang['10174'] = 'TransactionId is incorrect.'; //Transaction Id provided for the transaction is not correct.
$lang['10175'] = 'ForwardingInstitution Type is incorrect.'; //Forwarding Institution Type provided for the transaction is not correct.
$lang['10176'] = 'POSTypeId is not provided.'; //POS Type Id is not provided for the transaction.
$lang['10177'] = 'POSTypeId is incorrect.'; //POS Type Id provided for the transaction is not correct.
$lang['10178'] = 'UserId is not provided.'; //User Id is not provided for the transaction.
$lang['10179'] = 'Password is not provided'; //Password is not provided for the transaction.
$lang['10180'] = 'TerminalId is not provided'; //Terminal Id is not provided for the transaction.
$lang['10001'] = 'Card expired'; //Applicable for all APIs except CreateAndIssue
$lang['10002'] = 'Card is deactivated'; //Card is already moved to deactivated state.
$lang['10029'] = 'Card not activated.'; //Applicable for all APIs except CreateAndIssue.
$lang['10004'] = 'Could not find card. Please enter a valid card number.'; //Card number passed is invalid.
$lang['10012'] = 'Validation Failed'; //Validation failed.
$lang['10640'] = 'ToDate is incorrect.'; //This Response code is sent when the Transaction Filter (Begin Date > End Date)
$lang['10642'] = 'NoOfTransactions is incorrect.'; //The No of Transactions in Transaction Filter <= 0   
$lang['10645'] = 'StartIndex should be greater than 0'; //The Page Index in Transaction Filter <= 0    
$lang['10641'] = 'TransactionTypeIdTo Filter is incorrect.'; //The TransactionTypeIds in the Transaction Filter is Invalid Transaction Type or Multi Transaction Type.
$lang['10181'] = 'You cannot use future date as Transaction Date.'; //The Begin Date or EndDate (in the Merchant Time Zone if Multi Time Zone enabled) falls in the future date
$lang['10182'] = 'DateAtClient is not provided'; //Date at client is not provided for the transaction.
$lang['10183'] = 'DateAtClient is incorrect.'; //Date at client provided for the transaction is not correct.
$lang['10184'] = 'Invalid POS.'; //POS provided for the transaction is not valid.
$lang['10185'] = 'AcquirerId is not provided'; //Acquirer Id is not provided for the transaction.
$lang['10186'] = 'MerchantOutletName is not provided'; //Merchant Outlet Name is not provided for the transaction.
$lang['10194'] = 'Invalid Transaction Type'; //Transaction Type provided for the transaction is not valid.
$lang['10196'] = 'Amount is not provided'; //Amount is not provided for the transaction.
$lang['10197'] = 'Amount is incorrect.'; //Amount provided for the transaction is not correct.
$lang['10207'] = 'ForwardingEntityID is not provided'; //Forwarding Entity Id is not provided for the transaction.
$lang['10208'] = 'ForwardingEntityPwd is not provided'; //Forwarding Entity Password is not provided for the transaction.
$lang['10322'] = 'Invalid fund type'; //Fund Type provided for the transaction is not valid.
$lang['10372'] = 'Card already exists'; //If ImportCardNumber is already imported and the user tries to import the same, the transaction fails with this responseCode/responseMessage
$lang['10373'] = 'Special characters are not allowed in card number'; //If any other characters are present in ImportCardNumber apart from A-Z, a-z, 0-9 and -(hyphen) in request,  the transaction fails with this responseCode/responseMessage.
$lang['10375'] = 'Imported Cards are not supported for this merchant'; //Failure due to Program configuration
$lang['11037'] = 'Unable to locate the beneficiary.'; 
// If, 
//•   Beneficiary card or beneficiary PPI customer is not found via card number or mobile number provided in request. 
// •   Mobile number of the beneficiary does not match with mobile number sent in request. 
// •   More than one customer exists for provided mobile number. 
// •   More than one card exists for the customer. 
// •   The beneficiary card and source card do not belong to the same CPG. 
// •   Beneficiary card is inactive 
$lang['11038'] = 'Fund Transfer is not enabled. Please reach customer support for more details.'; //FundTranferP2PEnabled flag must be enabled to transfer fund.
$lang['11039'] = 'From and To account cannot be same.'; //Source card and destination card must not be same
$lang['11041'] = 'Invalid source account for fund transfer'; //Valid source account number must be configured for a CPG for fund transfer
$lang['11042'] = 'The request KYC details does not match Customer KYC details'; //KYC details sent in PPIKYCDetailsOld is incorrect
$lang['11043'] = 'Downgrade from Full to Minimum or No KYC is not allowed '; //PPI Customer tries to downgrade from Full KYC to Minimum KYC or no KYC
$lang['11044'] = 'Incomplete or invalid beneficiary details in the request.'; //When invalid beneficiary details are sent in a request or if mandatory beneficiary details are missing
$lang['11046'] = 'The Customer KYC Details Already Exists'; //KYC details sent in PPIKYCDetailsNew is already associated with another customer
$lang['11047'] = 'Sorry we are experiencing an error, please try after some time'; //If there is failure response from shift API, this response is sent to the end customer
$lang['11050'] = 'PPI program is not enabled'; //If a PPI program is not enabled/activated for the given customer
$lang['11051'] = 'Adding beneficiary is not supported'; //If a PPI program customer does not have Full KYC status
$lang['11052'] = 'Maximum limit of adding beneficiaries'; //If a customer is trying to add more Beneficiaries than the maximum limit configured at the system
$lang['11053'] = 'Monthly  transaction limit is exceeded'; //If a customer tries to transfer fund more than the preconfigured monthly transfer limit
$lang['11055'] = 'Validation failed at Payment Gateway'; //If balance is insufficient in the account or there is some issue with the partner bank
$lang['11056'] = 'Transaction cancelled'; //If transaction is cancelled
$lang['11057'] = 'Account validation failed'; //If validation failure occurs at payout module level
$lang['11058'] = 'Transaction Rejected'; //If transaction is rejected
$lang['11060'] = 'Beneficiary details already exist  '; //If Beneficiary is already added
