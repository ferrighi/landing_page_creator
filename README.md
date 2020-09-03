## Module: Landing Page Creator (landing_page_creator)

### Description: 
A Drupal module to create landing pages associated with DOIs requests. 
The workflow is the following: 
- the form page is available at "yoursite/landing-page-creator/form"
- upload an xml file with mmd specifications
- the mmd fields are translated into Datacite metadata format using https://github.com/steingod/mmd/blob/master/xslt/mmd-to-datacite.xsl which is included in the module under the includes directory
- metadata file is sent to Datacite using drupal_http_request
- DOI is returned and extracted
- Landing page (a drupal node of type "landing_page") is created using DOI as the landing page url alias
- URL of the landing page is registered to Datacite using drupal_http_request

### Permission:
To configure the permissions for this module, i.e. who can access the "landing-page-creator/form" page go to: "People->Permissions" and activate the "Access content for the Landing Page module" for the role of interest.

### Drupal 8 specific information:
The module should be placed in <web_root>/modules/metno for it to work properly
### Requirements

This module requires installation and/or configuration of external services as well as the creation of a specific content type . More specifically:   

* A DataCite account must be activated. The username, password, account prefix and enviroment (test/operational) must be configured in the administration page. The module validates if the fields in the configuration interface have been filled. The request will connect to:
https://mds.test.datacite.org
https://mds.datacite.org
depending on the evironment selected (test in the first case, operational in the second case). Accordingly the account information filled must metch the test or operational accounts.  
* The xsltproc tool should be installed (apt install xsltproc) to perform the on-the-fly translation between metadata standards.
* A Landing Page content type must be set up in the portal that is using this module. See below for the configuration of this content type.

### Configuration of the Landing Page content type

#### Content type

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


#### Select list: vocabularies

##### Iso Topic Category

- machine name: field_iso_topic_category
- number of values: unlimited

| key | values 
| --- | --- 
|farming| farming
|biota|biota
|boundaries|boundaries
|climatologyMeteorologyAtmosphere|climatologyMeteorologyAtmosphere
|economy|economy
|elevation|elevation
|environment|environment
|geoscientificInformation|geoscientificInformation
|health |health
|imageryBaseMapsEarthCover|imageryBaseMapsEarthCover
|intelligenceMilitary|intelligenceMilitary
|inlandWaters|inlandWaters
|location|location
|oceans|oceans
|planningCadastre|planningCadastre
|society|society
|structure|structure
|transportation|transportation
|utilitiesCommunication|utilitiesCommunication 

##### License 

- machine name: field_license
- number of values: 1

| key | values 
| --- | --- 
|C0|<span class="license-name">CC0</span><a href="https://creativecommons.org/share-your-work/public-domain/cc0/"><img src="icons/CC0.png" ></a>
|CCBY|<span class="license-name">CC-BY</span><a href="https://creativecommons.org/licenses/by/4.0/"><img src="icons/CC0.png" ></a>
|CCBYSA|<span id="license-name">CC BY-SA</span><a href="https://creativecommons.org/licenses/by-sa/3.0/"><img src="icons/CCBYSA.png" ></a>
|CCBYNC|<span id="license-name">CC BY-NC</span><a href="https://creativecommons.org/licenses/by-nc/4.0/"><img src="icons/CCBYNC.png" ></a>

