nz.co.fuzion.omngateway
=======================

OMNgateway payment processor extension for CiviCRM

Testing Notes
###############################
#- installation
###############################
CiviCRM has been bringing in a new installation method - I have the processor working using the 'old' way 
on the circus site (note that this is a live site but I can happily delete test contacts from it as long as you 
let me know
- the new installation method doesn't seem to quite work for local installs but is being 

######################################
#Transaction tests
######################################
- Contribution - -page - http://www.circus.org.nz/civicrm/contribute/transact?reset=1&id=1
                       -
- visa                       - 
successful contribution processed
Amount: N 1.00
Date: June 10th, 2012 10:54 PM
Transaction #: 31769
using test credit card :Visa - 4005 5500 0000 0019 (future expiry date & made up csv
(I also tested with a bunch on invalid characters in my name & it was fine - !#,'/\"@

- Mastercard rejected
Mastercard - 5424180279791765 exp: 04/13 - csv 000
Payment Processor Error message
9010: Error: [Referral] - from payment processor 

AMEX
373953244361001 - randon date - successful - needed 4 character CVS.
NOTE I added problematic characters to the contribution page before testing - ie. TEST PAGE!'#*"
Amount: $ 1.00
Date: June 10th, 2012 11:31 PM
Transaction #: 31773

DISCOVER
6011000993010978 April 2013 CSV 111
Payment Processor Error message
9010: Error: [Invalid Merchant number or Subscriber does not exist or is inactive] - from payment processor 