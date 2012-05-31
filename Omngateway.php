<?php

/*
 +----------------------------------------------------------------------------+
 | OMNGateway Core Payment Module for CiviCRM version 4.1 |
 +----------------------------------------------------------------------------+
 | Licensed to CiviCRM under the Academic Free License version 3.0            |
 |                                                                            |
 | Written & Contributed by Eileen McNaughton - Feb  2012 
 | Sponsored by OMNGateway            |
 +----------------------------------------------------------------------------+
*/

/**
 -----------------------------------------------------------------------------------------------
The basic functionality of this processor is that variables from the $params object
 are transformed into json
 The json is submitted to the processor's https site
using curl and the response is translated back into an array using the processor's function.

If an array ($params) is returned to the calling function the values from
the array are merged into the calling functions array.

If an result of class error is returned it is treated as a failure. No error denotes a success. Be
WARY of this when coding

 -----------------------------------------------------------------------------------------------
**/


require_once 'CRM/Core/Payment.php';

class nz_co_fuzion_omngateway extends CRM_Core_Payment
{
    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;

    /**********************************************************
     * Constructor
     *
     * @param string $mode the mode of operation: live or test
     *
     * @return void
     **********************************************************/

    function __construct( $mode, &$paymentProcessor )
    {
        $this->_mode             = $mode;   // live or test
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = 'OMNGateway';
    }

    /** 
     * singleton function used to manage this object 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return object 
     * @static 
     * 
     */ 
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new nz_co_fuzion_omngateway( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }

    /**********************************************************
     * This function is set up and put here to make the mapping of fields
     * from the params object  as visually clear as possible for easy editing
     *
     *  Comment out irrelevant fields
     **********************************************************/
    function mapProcessorFieldstoParams($params)
    { 
        /**********************************************************
         * compile array
         * Payment Processor field name fields from $params array
         **********************************************************/
         $requestFields = array(
           'version'   => '1.0',
           'authType'  => 'EO',
           'amount'    => $params['amount']* 100,// in cents
           'accountNo' => $params['credit_card_number'],
           'expr'      => sprintf('%02d', (int) substr ($params['year'], 2, 2).$params['month']),
           'cvv'       => $params[ 'cvv2'],
      //     'avs1'      => $params['street_address'],
        //   'avs2'      => $params['postal_code'],
         );
    return $requestFields;
    }
  

    /**********************************************************
     * This function sends request and receives response from
     * the processor
     **********************************************************/
    function doDirectPayment( &$params )
    {
        if ( $params['is_recur'] == true ) {
            CRM_Core_Error::fatal( ts( 'Omngateway - recurring payments not implemented' ) );
        }

        if ( ! defined( 'CURLOPT_SSLCERT' ) ) {
            CRM_Core_Error::fatal( ts( 'Omngateway / Nova Virtual Merchant Gateway requires curl with SSL support' ) );
        }

        /*
         *Create the array of variables to be sent to the processor from the $params array
         * passed into this function
         */
        $requestFields = self::mapProcessorFieldstoParams($params);

        /*
         * define variables for connecting with the gateway
         */
        $requestFields['username'] = $this->_paymentProcessor['user_name'];
        $requestFields['password'] = $this->_paymentProcessor['password'];
        $host             = $this->_paymentProcessor['url_site'];

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $requestFields );

        /**********************************************************
         * Check to see if we have a duplicate before we send
         **********************************************************/
        if ( $this->_checkDupe( $params['invoiceID'] ) ) {
            return self::errorExit(9003, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.' );
        }
        /**********************************************************
         * Convert to json using function below
         **********************************************************/
        $json = $this->buildjson($requestFields);


        /**********************************************************
         * Send to the payment processor using cURL
         **********************************************************/
$host = "https://omngateway.net/authorize/";
        $ch = curl_init ($host);
        if ( ! $ch ) {
            return self::errorExit(9004, 'Could not initiate connection to payment gateway');
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $json);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
           'Content-Type: application/json',                                                                                
           'Content-Length: ' . strlen( $json))   
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//civicrm doesn't force / facilitate proper certificate config
        curl_setopt($ch, CURLOPT_SSLVERSION , 3); // this seems to be a 'feature' of the OMNGateway - probably the Open SSL version requires this


        /**********************************************************
         * Send the data out over the wire
         **********************************************************/
        $responseData = curl_exec($ch);

        /**********************************************************
         * See if we had a curl error - if so tell 'em and bail out
         *
         * NOTE: curl_error does not return a logical value (see its documentation), but
         *       a string, which is empty when there was no error.
         **********************************************************/
        if ( (curl_errno($ch) > 0) || (strlen(curl_error($ch)) > 0) ) {
            curl_close($ch);
            $errorNum  = curl_errno($ch);
            $errorDesc = curl_error($ch);

            if ($errorNum == 0)                                               // Paranoia - in the unlikley event that 'curl' errno fails
                $errorNum = 9005;

            if (strlen($errorDesc) == 0)                                      // Paranoia - in the unlikley event that 'curl' error fails
                $errorDesc = "Connection to payment gateway failed";
            if ($errorNum = 60) {
                return self::errorExit( $errorNum, "Curl error - ".$errorDesc." Try this link for more information http://curl.haxx.se/docs/sslcerts.html" );
            }

            return self::errorExit( $errorNum, "Curl error - ".$errorDesc." your key is located at ".$key." the url is ".$host." json is ".$json." processor response = ". $processorResponse );
        }
    
        /**********************************************************
         * If null data returned - tell 'em and bail out
         *
         * NOTE: You will not necessarily get a string back, if the request failed for
         *       any reason, the return value will be the boolean false.
         **********************************************************/
        if ( ( $responseData === false )  || (strlen($responseData) == 0) ) {
            curl_close( $ch);
            return self::errorExit( 9006, "Error: Connection to payment gateway failed - no data returned.");
        }
    
        /**********************************************************
         // If gateway returned no data - tell 'em and bail out
         **********************************************************/
        if ( empty($responseData) ) {
            curl_close( $ch);
            return self::errorExit( 9007, "Error: No data returned from payment gateway.");   
        }

        /**********************************************************
         // Success so far - close the curl and check the data
         **********************************************************/
        curl_close( $ch);

        /**********************************************************
         * Payment succesfully sent to gateway - process the response now
         **********************************************************/

        $processorResponse = json_decode($responseData);
        /*success in test mode returns response "APPROVED"
         * test mode always returns trxn_id = 0
         * fix for CRM-2566
         **********************************************************/

        if ( $processorResponse->responseCode == 1 ) {
            return self::errorExit( 9010, "Error: [" .$processorResponse->responseText ."] - from payment processor");  
        }
        if ( $processorResponse->responseText == 'Approval') {
            if ( $this->_mode == 'test')  {
                $params['trxn_id'] = 'test' . $processorResponse->transactionNo;//'trxn_id' is varchar(255) field. returned value is length 37
              
            } else {
               $params['trxn_id'] = $processorResponse->transactionNo;//'trxn_id' is varchar(255) field. returned value is length 37
            }
            $params['trxn_result_code'] = $processorResponse->authCode ;

            return $params;            }
    } // end function doDirectPayment

    /**
     * Checks to see if invoice_id already exists in db
     * @param  int     $invoiceId   The ID to check
     * @return bool                  True if ID exists, else false
     */
    function _checkDupe( $invoiceId )
    {
        require_once 'CRM/Contribute/DAO/Contribution.php';
        $contribution = new CRM_Contribute_DAO_Contribution( );
        $contribution->invoice_id = $invoiceId;
        return $contribution->find( );
    }

    /**************************************************
     * Produces error message and returns from class
     **************************************************/
    function &errorExit ( $errorCode = null, $errorMessage = null )
    {
        $e = CRM_Core_Error::singleton( );
        if ( $errorCode ) {
            $e->push( $errorCode, 0, null, $errorMessage );
        } else {
            $e->push( 9000, 0, null, 'Unknown System Error.' );
        }
        return $e;
    }


    /**************************************************
     * NOTE: 'doTransferCheckout' not implemented
     **************************************************/
    function doTransferCheckout( &$params, $component )
    {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) );
    }


    /********************************************************************************************
     * This public function checks to see if we have the right processor config values set
     *
     * NOTE: Called by Events and Contribute to check config params are set prior to trying
     *  register any credit card details
     *
     * @param string $mode the mode we are operating in (live or test) - not used
     *
     * returns string $errorMsg if any errors found - null if OK
     *
     ********************************************************************************************/
    //  function checkConfig( $mode )          // CiviCRM V1.9 Declaration
    function checkConfig( )                // CiviCRM V2.0 Declaration
    {
        $errorMsg = array();

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $errorMsg[] = ' ' . ts( 'ssl_merchant_id is not set for this payment processor' );
        }

        if ( empty( $this->_paymentProcessor['url_site'] ) ) {
            $errorMsg[] = ' ' . ts( 'URL is not set for this payment processor' );
        }

        if ( ! empty( $errorMsg ) ) {
            return implode( '<p>', $errorMsg );
        } else {
            return null;
        }
    }//end check config

    function buildjson($requestFields)
    {
       $body = '{"auth": {';
       $sep = '';
       foreach($requestFields AS $key => $val){
         $body .= $sep . '"' . urlencode($key) . '": "' . urlencode($val) . '"';
         $sep = ', ';
       }
       $body .= '}}';
       return $body;
    }

}