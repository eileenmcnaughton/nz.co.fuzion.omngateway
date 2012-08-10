nz.co.fuzion.omngateway
=======================

OMNgateway payment processor extension for CiviCRM

Testing Notes
###############################
#- installation
###############################
This Extension is packaged for the new CiviCRM extensions installation. You should install this on a CiviCRM 4.1 or later site. On your CiviCRM site, go to 'manage extensions' (under adminster/ customize data and screens) through the UI. Choose to enable the extension. You can then create a OmnGateway processor through
Administer/System Settings/PaymentProcessors.

####
# Testing status
############

Tested installation on a 4.2 install. I also installed the processor from github to a 4.1 site &
did the tests described below.

Test credentials = test for both username & password

Test cards are as follows
Mastercard - 5424180279791765 exp: 04/12
Amex - 373953244361001 exp: 04/12 
Visa - 4005 5500 0000 0019 exp: 04/12
Disc - 6011 0009 9301 0978 exp 04/12 will give decline

All 1.00



######################################
#Transaction tests
######################################
These tests were carried on out a 4.1 live site
I tested visa for 
- backoffice contribution
- Contribution page http://www.circus.org.nz/civicrm/contribute/transact?reset=1&id=1
- Event page http://www.circus.org.nz/civicrm/event/register?id=761&reset=1

                       
- visa
successful contribution processed
Amount: N 1.00
Date: June 10th, 2012 10:54 PM
Transaction #: 31769
using test credit card :Visa - 4005 5500 0000 0019 
future expiry date & made up csv
(I also tested with a bunch on invalid characters in my name & it was fine - !#,'/\"@

- Event contribution 
Event Total:  $ 1.00
Transaction Date: June 11th, 2012 12:17 AM
Transaction #: 31781 

- Incorrect credit card (4111111111111111)
Payment Processor Error message
9010: Error: [Declined] - from payment processor 


- Mastercard rejected
Mastercard - 5424180279791765 exp: 04/13 - csv 000
Payment Processor Error message
9010: Error: [Referral] - from payment processor 

- AMEX
373953244361001 - randon date - successful - needed 4 character CVS.
NOTE I added problematic characters to the contribution page before testing - ie. TEST PAGE!'#*"
Amount: $ 1.00
Date: June 10th, 2012 11:31 PM
Transaction #: 31773

- DISCOVER
6011000993010978 April 2013 CSV 111
Payment Processor Error message
9010: Error: [Invalid Merchant number or Subscriber does not exist or is inactive] - from payment processor 