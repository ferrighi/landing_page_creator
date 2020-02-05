# Module: landing_page_creator

## Description: 
A Drupal module to create landing pages associated with DOIs requests. 
The workflow is the following: 
- upload an xml file with mmd specifications
- the mmd fields are translated into Datacite metadata format using https://github.com/steingod/mmd/blob/master/xslt/mmd-to-datacite.xsl which is included in the module under the includes directory
- metadata file is sent to Datacite using drupal_http_request
- DOI is returned and extracted
- Landing page (a drupal node of type "landing_page") is created using DOI as the landing page url alias
- URL of the landing page is registered to Datacite using drupal_http_request

## Requirements
This module requires to have an account on DataCite. 
- The username, password, account prefix and enviroment (test/operational) must be configured in the administration page. The module validates if the fields in the configuration interface have been filled. The request will connect to:
-- https://mds.test.datacite.org
-- https://mds.datacite.org
depending on the evironment selected (test in the first case, operational in the second case). Accordingly the account information filled must metch the test or operational accounts.  
- The xsltproc should be installed to perform the on-the-fly translation between metadata standards.
- A Landing Page content type must be set up in the portal that is using this module. See below for the configuration of this content type.

## Configuration of the Landing Page content type

# Content type

A content type (CT) must be set up in the Drupal site. 
- the manchine name of the CT: landing_page

- the fields of the content type must be as follow:

|Machine name| type | widget |
|---         |---   |---     |
|body                    |Long text and summary |Text area with a summary  |
|field_abstract          |Long text and summary |Text area with a summary  |
|field_iso_topic_category|List (text)           |Select list               |
|field_doi               |Link                  |Link                      |
|field_citation          |Long text             |Text area (multiple rows) |
|field_license           |List (text)           |Select list               |
|field_contact           |Long text             |Text area (multiple rows) |
|field_north             |Float                 |Text field                |
|field_south             |Float                 |Text field                |
|field_east              |Float                 |Text field                |
|field_west              |Float                 |Text field                |
|field_access            |Long text             |Text area (multiple rows) |
|field_bnds              |Geofield              |Bounds                    |
|field_start_date        |Date (ISO format)     |Pop-up calendar           |
|field_end_date          |Date (ISO format)     |Pop-up calendar           |
|field_lp_tag            |Entity Reference      |Select list               |


# Select list: vocabularies

| field | key/values |
| field_iso_topic_category |
farming| farming
biota|biota
boundaries|boundaries
climatologyMeteorologyAtmosphere|climatologyMeteorologyAtmosphere
economy|economy
elevation|elevation
environment|environment
geoscientificInformation|geoscientificInformation
health |health
imageryBaseMapsEarthCover|imageryBaseMapsEarthCover
intelligenceMilitary|intelligenceMilitary
inlandWaters|inlandWaters
location|location
oceans|oceans
planningCadastre|planningCadastre
society|society
structure|structure
transportation|transportation
utilitiesCommunication|utilitiesCommunication |  











