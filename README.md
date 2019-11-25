# Module: landing_page_creator
## Description: 
A Drupal module to create landing pages associated with DOIs requests. 
The workflow is the following: 
- upload an xml file with mmd specifications
- the mmd fields are translated into Datacite using https://github.com/steingod/mmd/blob/master/xslt/mmd-to-datacite.xsl
- metadata file is sent to Datacite
- DOI is returned and extracted
- Landing page is created using DOI
- URL of the landing page is registered to Datacite

## Requirements
This module requires to have an account on DataCite. 
- The username, password and account prefix should be configured in the administration page
- The xsltproc should be installed 
