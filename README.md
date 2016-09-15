# phpWhatTheLog (phpWTL)
### a PHP logging framework

Michael Beyer, 07-2016, rev. 0.3.2-alpha (2016/09/16)
<br/>
<mgbeyer@gmx.de>

<br/>

<a name="_top"></a>
I say this right off the bat: If you have your PHP project hosted on a *proper* webspace (like a typical LAMP envirnonment), and so most probably have straight access to your Apache logfiles, you won't need this. Go get your real-life webserver logs, feed them to the analyzer of your liking, be thankful for this bliss and enjoy your carefree life you lucky bastard :)
<br/>
But if you belong to the group of people - like I do - who like to scrounge from freehosting services, which allow marauding freeloaders like us to do shoot with their account, this little project here might be something for you... so be my guest...

<br/>

>**_PREFACE_** &nbsp;&nbsp;
[PURPOSE](#preface)
~
[FEATURES](#features)
~
[LIMITATIONS](#drawbacks)
~
[GENERAL USAGE](#usage)

>>**_ACCESS LOGS_** &nbsp;&nbsp;
[THE LOGGER](#logger)
~
[LOGGER PARTS](#logger_parts)
~
[RETRIEVAL POLICIES](#logger_usage)
~
[CODE USAGE](#code_usage)
~
[ENCODING](#encoding)

>>>**_LOG STORAGE_** &nbsp;&nbsp;
[FILE LOG WRITER](#file_writer)
~
[WRITING CSV DATA](#csv_data)
~
[DATABASE LOG WRITER](#db_writer)

>>>>**_EVENT LOGS_** &nbsp;&nbsp;
[LOGGING PHP SCRIPTS](#php_logger)
~
[LOG BUFFER](#log_buffer)

>>>>>**_REFERENCES_** &nbsp;&nbsp;
[API DOCUMENTATION](#apidoc)
~
[SUPPORTED FORMATS](#formats)

>>>>>>**_ALL THE REST_** &nbsp;&nbsp;
[CONTRIBUTING](#contributing)
~
[LICENSE](#license)
~
[OTHER STUFF](#drawback_1)

<br/>

<a name="preface"></a>
### What is a PHP logger good for anyway? It's merely a simulation of real webserver logging! [(^)](#_top)

Yes, right. Though a PHP logger can "simulate" quite much here, it's  in the nature of things it has its limitations. A PHP script only can do so much. It's just not in the best place of the "request-response-foodchain" to fully replace a webserver which is *just* in the right spot for logging. What those limitations look like and how phpWhatTheLog addresses them I'll discuss in a minute... first let me show you what this little project here *can* manage to do for you! There's even more to it than just statistical access logging...

<br/>

<a name="features"></a>
### Logging hits to your PHP application(s) (and more) - as authentic, painless and also flexible as possible [(^)](#_top)

Key features of phpWhatTheLog include:

* Statistical access logging similar to a webserver:
	* Logging in Combined, NCSA Common or W3C Extended format out-of-the-box, just like the real thing (see: *[SUPPORTED FORMATS](#formats)*).
* PHP application error/event logging:
	* Loglevel, optional buffered logging/storage, context data/array support, exception object support, context value interpolation.
* Highly modular and customizable architecture (e.g. logging itself is independent from actual log data storage).
* Character encoding facility for log data output.
* Flexible log storage:
	* Logging the "classic" way to a file, with log rotation capabilities.
	* Writing customizable CSV data
	* Storing logs in a relational database, vendor independent by using a PDO based abstraction layer (Doctrine 2 DBAL).
* The architecture allows for loosely coupled modules so developers can easily modify and/or replace logically related functionality (logging format, data retrieval, data validation, data formatting, log writing).
* Many useful configurable settings for logging and log storage (e.g. logging multiple applications separately).

<br/>

<a name="drawbacks"></a>
### Common drawbacks of logging by script - and how to deal with them (maybe) [(^)](#_top)

* You can't expect to log everything (and you can't expect some of the things you actually *can* log to be logged easy-peasy)
	* To log a hit to the script you include the logger functionality is pretty straight-forward. Logging access to collateral (static) files is not *that* straight. [Read more about it...](#drawback_1)
	* Another example is the *cs-bytes* field (bytes received from request) from the W3C Extended format. It's a good example for data which a webserver can easily come by but which just can't be retrieved by PHP ($_SERVER['CONTENT_LENGTH'] is not set if the request came in via GET method).
* Reliable data for some of the fields is hard to come by
	* For example take the "content-length" field from both the "Common" and "Combined" format. It's supposed to contain the size of the object returned to the client in bytes and trypically goes into the HTTP response header. Problem is: It is unclear how big the output of your script really is until it terminates.  [Read more about it...](#drawback_2)
	* A further example is the *time-taken* field of the W3C Extended format. The reasons why in this case you can only reach for an approximation is quite similar to the example above. A webserver is just in the better position to perform a more accurate measurment.

<br/>

#### But limitations aside...
<a name="usage"></a>
## Let's use this thing! [(^)](#_top)

Include the logger and writer of your liking (and probably some helper classes, too) into your script(s):

	use phpWTL\phpWTL;
	use phpWTL\CombinedLogger;
	use phpWTL\CommonCombinedDRP;
	use phpWTL\DataRetrievalPolicy;
	use phpWTL\DataRetrievalPolicyHelper;
	use phpWTL\LogWriter\FLW\FileLogWriter;

	define('PATH_TO_PHPWTL', '/path/to/your/phpwtl/installation/');
	require_once PATH_TO_PHPWTL.'phpWTL.php';
	require_once PATH_TO_PHPWTL.'phpWTL/CombinedLogger.php';
	require_once PATH_TO_PHPWTL.'phpWTL/LogWriter/FLW/FileLogWriter.php';


<a name="logger"></a>
### The logger [(^)](#_top)

A logger object represents a certain log format. It knows and handles all classes/objects neccessary - data retriever, data validator and data formatter:

<a name="logger_parts"></a>	
#### The single parts associated with a logger [(^)](#_top)

On account of the modularized structure of phpWTL a logger attaches several functional parts to itself. Basically a logger is nothing but an intelligent container for the following independent functionalities:

* **format descriptor**
Format definition blueprint. Basically an array of DescriptorField objects containing metadata describing all log format fields:
	* **name**: Field Id
	* **caption**: Field title/appearance
	* **prefix**: Field delimiter, start
	* **suffix**: Field delimiter, end
	* **formatter**: Format (conversion) specifications (e.g. for a timestamp)
	* **validator**: Regular expression which can be used to validate field contents
	* **datatype_raw**: Type of raw data to use with a database log writer for mapping/abstraction layer purposes (for more information on data types see section below).
	* **datatype_formatted**: Type of formatted data to use with a database log writer for mapping/abstraction layer purposes (for more information on data types see section below).
	* **default**: If the field should be omitted from the output of a content object by default, set to "false" (boolean). <br/><sub>*Note:* If set to "false", data for this field will be retrieved anyway and also the formatter and validator will operate. The content will be stored in the content object, it will just be ignored by methods actually giving back the data.</sub>
	* **meta**: If the field should be considered a format meta field, set to "true" (boolean)

* INFORMATION ON *DATA TYPES*: Currently data types are only used by the database log writer (DBLW) to create or alter tables for the log data. A validator (or any other module) could use them as well. New types could be defined and used freely. But by convention data types correlate strongly with basic types used by Doctrine DBAL with some minor exceptions (which are converted to Doctrine DBAL types with special default portable options).
  <br/><br/>*RAW VS. FORMATTED DATA* **Please note:** There's a distinction between datatypes for raw and formatted data because a formatter might alter raw data in a way it won't fit its original type anymore and so the database abstraction layer would have trouble matching the data to a database field. A good example is a timestamp from the "*Combined*" format. Originally it is a datetime type. But the format specification alters the format and puts delimiters around the data so it becomes a string type. So if you want to write data to a database which fully adheres to the *Combinded* specification you have to make it a string type in the database (the raw type won't fit anymore). With two different datatype settings at hand you can tell the database log writer whether to store a raw or a formatted version of the data.
  <br/><br/>The *"DatabaseLogWriterHelper"* class provides methods to retrieve the proper DBAL datatype for a given DBLW datatype if in doubt, but more often than not they are identical (*"getDBALDataType"*). Use *"getDataTypeDefaultsForDBAL"* to get defaults (DBAL portable options) for a given data type. The *"mapDBALDataType"* method might be used to bundle those two methods and give back an associative array containing *datatype* and *options*, rules are as follows:
	* *id:* will be converted to "*integer*" with "*notnull*" set to *true*, "*autoincrement*" set to *true*
	* *integer:* aliases are "*int*" or "*smallint*" with "*notnull*" set to *false*
	* *bigint:* will be converted to "*string*" and have its defaults
	* *string:* will have "*length*" set to *1024* and "*notnull*" set to *false*
	* *boolean:* alias is "*bool*"
	* *datetimetz:* will be converted to "*datetime*" and have its defaults
	* *timestamp:* will be converted to *integer* and "*notnull*" set to *false* (unfortunately Doctrine DBAL does currently *not* support a real timestamp database type)
	* other possible Doctrine conform types: *decimal*, *float*, *text* (all with "*notnull*" set to *false*), *date*, *time*, *datetime*
	
* **data retrieval policies**
An array of DataRetrievalPolicy objects describing one or more instructions for a logger and data retriever on how to handle data retrieval for special occasions:
	* **name**: Policy Id
	* **flag**: Specific behavior of this policy
	* **parameter**: Might be used to parameterize a policy bahavior (the parameter might be any type, non-scalar and two-ways)

* **logger content**
Encoding-aware container for log data built based on the format descriptor.

* **data retriever**
The class actually retrieving the data for all fields defined by the format descriptor. The data retriever's behavior can be controlled by retrieval policies.

* **data validator**
This might be used to validate retrieved data. It's there for the sake of modular expandability and currently serves no real purpose. But hey, it's there already, who knows who might need this in the future ;-)

* **data formatter**
Some log formats require the data for certain fields to be surrounded by special delimiters. The content of other fields might be formatted in many different ways (e.g. a timestamp). A formatter might be used to adress those requirements.

<a name="logger_usage"></a>	
#### Getting started with the logger [(^)](#_top)
Get started by fetching a logger instance:

	$logger= CombinedLogger::getInstance($myPolicies);

If you want, you might provide the constructor with an array of policy objects. If you omit the parameter, logger defaults will be used. Currently for the Combined and Common loggers this is the "DRP_CC_CONTENT_LENGTH_RETRIEVAL" policy set to "DRP_CC_CLR_SCRIPT" measurement.
Static class "CommonCombinedDRP" provides you with all neccessary constants. The "DataRetrievalPolicyHelper" class provides operations to deal with arrays of "DataRetrievalPolicy" objects. Here's an example for a custom policy setup:

	$myPolicies= array(
		new DataRetrievalPolicy(
			array(
				'name' => CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL, 
				'flag' => CommonCombinedDRP::DRP_CC_CLR_CUSTOM,
				'parameter' => 'images/test.jpg'
			)
		)
	);

Here's another example for one of several helper methods (obtain the parameter for a specific policy):

	echo DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter($logger->getDataRetrievalPolicies(), CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL);	
	
You might as well change policies after instantiation of the logger. Don't worry about the retriever for the policies will be passed through:

	$logger->setRetrievalPolicies($myPolicies);	
		
<a name="drp_cc"></a>	
#### The "DRP_CC_CONTENT_LENGTH_RETRIEVAL" policy (Common and Combined logger)

* **DRP_CC_CLR_SCRIPT**
Per default, the request target is the PHP script the "log" method is called from.
This means the content-length of the response object given to the client is estimated by looking up the size of the actual PHP script file.
This *might* come close to the real output produced by the script but also might differ big time (largely depending on your code).

* **DRP_CC_CLR_BUFFER**
If this flag is set, PHP output control (buffering) will be used to actually calculate the size of content produced by the script and sent back to the client.
This might not be exactly the value a webserver would see and may be off by a couple bytes due to measurement/script-injection logistics but it comes pretty close to a real webserver log entry.
The main drawback of this method is that now the whole output of the script will be buffered. While output buffering is active no output is sent from the script (other than headers). 
Instead the output is stored in an internal buffer until the "log" method is called (at this point content-length will be measured and the whole buffer will be sent "en bloc").
Most of all this means if possible you should instantiate your logger right at the beginning of your script (this will start the buffer) but call the "log" method not before the end of your script (which will terminate and send the buffer).
Whatever output is produced before the instantiation and after calling "log" can and will not be measured.

* **DRP_CC_CLR_CUSTOM**
You might provide the relative filename/path to another ressource (like an image) via this flag using "parameter". This affects the fields *"content_size"*, *"status_code"*, 
the request URI part of *"request_line"* (common format) and the *"referrer"* field (combined format) as follows: 
	* **"content_size"** will contain the file size of the given ressource (the new request target) or is empty if the ressource-file doesn't exist
	* **"status code"** will contain "200" if the target exists and "404" otherwise
	* **"request_line"** will now contain the new given target prefixed with the path to the php script the "log" method is called from (and thus is seen as relative to it)
	* **"referrer"** will now contain the request URI (plus query string if available) of the php script the "log" method is called from (normally this would go into "request_line")	
	
	
<a name="code_usage"></a>	
#### A closer look at the single parts of a logger [(^)](#_top)	
	
The logger contains several methods to provide information about itself (and its associated parts), like to show the format description, version, prefix (here: combined) or standard field delimiter (for a *"toString-like"* representation of the logger content object). [For more information see the API reference](#apidoc):

	// show format prefix
	echo $logger->getFormatDescriptor()->getformatPrefix();
	
	// get standard format field delimiter
	echo $logger->getFormatDescriptor()->getformatFieldDelimiter();

	// show all field names available
	// "FormatDescriptorHelper" provides constants to handle the "default" field flag:
	// DEFAULT_ANY, show all fields
	// DEFAULT_ONLY, show only fields flagged as "default" (default)
	// DEFAULT_NONE, show non-default fields only
	print_r($logger->getFormatDescriptor()->getFieldNames());
	
	// show only regular default fields (no meta fields)
	$default_fields_only= FormatDescriptorHelper::DEFAULT_ONLY;
	print_r($logger->getFormatDescriptor()->getRegularFieldNames($default_fields_only));
	
	// show all meta fields
	print_r($logger->getFormatDescriptor()->getMetaFieldNames());
	

#### Altering log format properties (and so changing aspects of logging bahavior)
	
Before you perform the actual logging you might alter properties of the log format via the descriptor object (like delimiters, formatters or validators). 
<br/>
Here's an example on how to change a field formatter. In this case the (Apache compliant) way an empty *content_size* will be handled in the "Common" and "Combined" formats (per default '%b' returned as a hyphen, while '%B' changes it to a zero):

	$logger->getFormatDescriptor()->setFormatter("content_size", "%B");
	
Here's another example on how to change a log field's *"default"* property, i.e. if field content will be given back per default by the *"toString"* and *"toArray"* methods of the content object if no whitelist parameter is provided:

	$logger->getFormatDescriptor()->setDefault("content_size", false);

<br/>
Now have the logger perform the actual logging (data retrieval, validation and formatting based on the logger's format descriptor):

	$validationErrors= $logger->log($params);
	
The *"$params"* parameter array controls whether the validator and/or the formatter should run after data retrieval (bool "validator" default "false", bool "format" default "true"). If the formatter is turned off, raw data for all fields will be retrieved.


#### Changing log fields in retrospect

You might want to individually change fields content after logging (but then you'll have to apply validator or formatter yourself afterwards if needed):	

	$myVal= "hello world!";
	if ($logger->getDataValidator()->isValid("referrer", $myVal)) {
		$logger->getDataRetriever()->setFieldContent("referrer", $myVal);
		$logger->getDataFormatter()->formatAllField("referrer");
	}
	
	
#### The logger content object

This is the interface to the actual log data:

	$logger->getLoggerContent();	
	
The logger content object offers several methods to retrieve data stored inside of it which can be utilized to "feed" a log writer (more on this topic see next section(s)).

<a name="encoding"></a>	
#### Content encoding (output filter) [(^)](#_top)

You might want to control the character encoding for all data coming from the content object and retrieved by its methods (which is by default disabled). This feature works like a filter. Each attribute associated with a logger content object is encoded accordingly each time the __get() method is called. However, everything written to the content object (via its __set() method) won't be encoded at all. 
<br/>
This is because data should be logged straight forward "as it is" without a pre-determined or forced encoding other than the PHP/system default your scripts are running on (which phpWTL assumes and is able to detect). If you choose to establish your own logger/data retriever, which performs encoding itself, you won't need this filter anyway and should keep it disabled.

	// individual encoding
	$my_encoding= "UTF-8";
	
	// phpWTL's default encoding (UTF-8)
	$my_encoding= phpWTL::DEFAULT_ENCODING;
	
	// use default encoding from PHP configuration
	$my_encoding= phpWTL::SYSTEM_ENCODING
	
	// disable encoding altogether
	$my_encoding= null;
	$my_encoding= "";
	
	$logger->getLoggerContent()->setEncoding($my_encoding);
	
	// get encoding setting used by getter methods
	$encoding= $logger->getLoggerContent()->getEncoding();

So you *should* know your data encoding if you establish your own logger/data retriever architecture. Nevertheless and for the sake of flexibility: You might want to tell the logger content object which encoding it should *assume* its data is supposed to have. This can be done in either of three "flavors":
 
	// individual encoding assumption
	$my_assumption= "UTF-8";
	
	// assume system/PHP default encoding (default)
	$my_assumption= phpWTL::ENCODING_ASSUMPTION_SYSTEM;

	// probe data (individually) for character encoding (needs "mbstring"-enabled PHP)
	$my_assumption= phpWTL::ENCODING_ASSUMPTION_PROBE_DATA;

	$logger->getLoggerContent()->setEncodingAssumption($my_assumption);
	
	// get encoding assumption setting
	$encoding_assumption= $logger->getLoggerContent()->getEncodingAssumption();
	
If you use *"phpWTL::ENCODING_ASSUMPTION_PROBE_DATA"* to "auto-detect" encoding and you dabble in "exotic" kinds of encoding, this method needs a little help from you. By default phpWTL checks for the "usual suspects" and assumes a priority order which is considered useful in most standard cases:

	UTF-8, Windows-1252 (*), ISO-8859-1
<small>(*) Because of a mbstring bug (see below) this entry has no effect. But it's there for a potential bugfix in the future ;)</small>

This means the detection algorithm just keeps trying, in order, the encodings you specified and then returns the first one under which the bytestream represented by the given string would be valid. The order ranges from "less specific" to "more specific". So if a given string which is supposed to be *"ISO"* doesn't contain special ISO specific characters, the first proper match will be taken (UTF-8). So auto-detection will say: "This string is UTF-8". You can change this behavior to anything you want and consider useful for your scenario:

	$default_order= $logger->getLoggerContent()->getEncodingDetectionOrder();
	$new_order= array_merge($default_order, array('KOI8-R'));
	$logger->getLoggerContent()->setEncodingDetectionOrder($new_order);
	
**Some important aspects to consider!**
* Please note that the encoding functionality in general needs a PHP installation with "iconv" enabled (this should be built-in since v. 5.6.0). So no *iconv* means no encoding filter whatsoever!
* For the *"phpWTL::ENCODING_ASSUMPTION_PROBE_DATA"* option the "mbstring" extension is needed in addition. If phpWTL detects its absence, it will use the *"phpWTL::ENCODING_ASSUMPTION_SYSTEM"* as a fallback. If this method fails as well, encoding won't work unless you provide a specific individual encoding assumption.
* Also keep in mind that because of a bug in the *"mbstring"* package the *"phpWTL::ENCODING_ASSUMPTION_PROBE_DATA"* option won't work for *Windows-1252* (ANSI) encoded data (it will assume *ISO-8859-1* encoding in this case).
	
**What if I have different encodings in multiple fields of my logger content object?**
<br/>
Well then I must say: Your data is bad, your design is bad and you should FEEL bad ;-)
<br/>
Maybe the *"PROBE_DATA"* encoding assumption strategy might help you (it checks the encoding of each property of your content object on a individual basis). But don't fret, there's a helper method which can  assist you in straighten up your data (if *"mbstring"* is available):

	$content= $logger->getLoggerContent();
	$content->straightenUpEncodingAll($my_assumption, $my_encoding);
	
	// example: try to straighten mixed encoding to system default
	$content->straightenUpEncodingAll(phpWTL::ENCODING_ASSUMPTION_PROBE_DATA, phpWTL::SYSTEM_ENCODING);
	
This method iterates thru all attributes and sets the encoding to "$my_encoding" based on the "$my_assumption" setting/strategy (as described above). Now all the data in your content object should be encoded consistently. I hope you're happy now!
<br/>
You can probe and set the encoding for single logger content fields, too (but if your design is straight you shouldn't need those methods):

	$content= $logger->getLoggerContent();
	$content->probeFieldEncoding("referrer");
	$content->setFieldEncoding("referrer", phpWTL::ENCODING_ASSUMPTION_SYSTEM, "KOI8-R");
	
<br/>	

	
<a name="file_writer"></a>	
### The file log writer (FLW) [(^)](#_top)

First instantiate a writer (here a classical file log writer):

	$writer= new FileLogWriter();
	
This writer will log their internal error, warnings and state into corr. public variables ("error" array, "warning" array and "state" string):	

	echo "ERRORS: ";
	print_r($writer->error);
	echo "WARNINGS: ";
	print_r($writer->warning);
	echo "log writer state: ".$writer->state;

If everything is ready use the "getLoggerContent" method from your logger to obtain the log information to pass on to your writer.
"__toString" magic is used to build a log entry string representation of the content object which you can feed to a log writer:

	$writer->writeToLog($logger->getLoggerContent());
	
You might want to change the standard field delimiter (as pre-defined in the format descriptor, for Common/Combined and Extended this is the space character) for a line-representation of your logger content object beforehand:

	$logger->getLoggerContent()->setFieldDelimiter(",");
	
The LoggerContent object offers a couple methods which might be utilized in situations where

1) not all of the fields are intended to be written to the log
1) additional fields are intended to be written to the log which are not flagged as "default"
1) log fields are intended to be written in a specific non-default order
		
**toString($whitelist)** might be used for a delimiter-separated string representation, **toArray($whitelist)** (**toArrayMeta($whitelist)**) might be used to obtain an associative array of (meta) fields and their contents, with field names as keys. **$whitelist** contains an array of field names and specifies the fields given back and their order. If **$whitelist** is omitted only fields which are flagged as *"default"* will be returned. 
<br/>
There are even more methods (e.g. to retrieve arrays of trimmed content (stripped prefix/suffix) or to retrieve the data types as well) so for more information see the [API reference](#apidoc).

*Tip:* Methods returning arrays of field content use field names as their keys. The helper method *"fieldNames2Captions"* might be used to change keys in such an array to field captions:

	$content= $logger->getLoggerContent()->toArray();
	$content= $logger->getLoggerContent()->fieldNames2Captions($content);

Here's an example how to use some of those methods (Combined format):

	// delete a specific key
	$keys_to_show= $logger->getFormatDescriptor()->getFieldNames();
	$key_to_delete= array_search("user_id", $keys_to_show);
	if ($key_to_delete) unset($keys_to_show[$key_to_delete]);
	
	// reverse order of all keys
	$keys_to_show= array_reverse($keys_to_show);
	
	// alternatively just define a whitelist
	$keys_to_show= array("timestamp", "host_ip", user_agent);
	
	// obtain information through LoggerContent object and write to log
	$writer->writeToLog($logger->getLoggerContent()->toString($keys_to_show));
	
#### The W3C Extended log file format special treatment
	
The W3C Extended log file format needs a slightly modified (*"extended"* ;-)) file writer (*"FLWext"*) to deal with the storage of directives right at the beginning of a fresh log:

	// instantiate extended log writer
	$writer= new FileLogWriterExt();

	// the default way to write things to the log ($logger is a "ExtendedLogger")
	$writer->writeToLogExt($logger->getLoggerContent(), $logger->buildDirectivesForFileWriter());
		
	// you might want to change which fields are written to the log and in which order
	$keys_to_show= $logger->getFormatDescriptor()->getRegularFieldNames();
	$key_to_delete= array_search("time-taken", $keys_to_show);
	if ($key_to_delete) unset($keys_to_show[$key_to_delete]);
	$keys_to_show= array_reverse($keys_to_show);
	$writer->writeToLogExt(
		$logger->getLoggerContent()->toString($keys_to_show), 
		// provide custom field names and order to build "Fields" directive
		$logger->buildDirectivesForFileWriter($keys_to_show)
	);

The *"writeToLogExt"* is a quite generic method: The second parameter is just an array containing lines to be written on top of a fresh logfile. So this could be used to write any sort of meta-data on top of a new logfile which is intended to be written only once (like header information for a CSV file).
	
#### FLW main configuration	(*FLW-default.ini*)

* **eol_method**
Method of line termination:
	* auto (default): Line terminator depends on the OS phpWTL is running on (auto detection)
	* windows | win: Force Windows EOL (\r\n)
	* unix | linux: Force Unix EOL (\n)
	* custom: Line terminator as specified in *"eol_sequence"*
* **eol_sequence**
Character(s) for custom line terminator (EOL)
* **logs_path**
Name of logs folder (relative path to your webspace document root), default "logs". Note: Any path leading above or equal level to the webspace document root will be ignored and replaced with the default for security reasons.
* **base_name**
Logfile basic name.
* **rotation_policy**
Logfile rotation policy, possible values are: hourly | h, daily | d (default), weekly | w, monthly | m, yearly | y | annual | a.
The format of your logfile name extension (timestamp) is set automatically based on this setting:
	* hourly: Y-m-d-H
	* daily: Y-m-d
	* weekly: Y-W
	* monthly: Y-m
	* yearly: Y
* **htaccess_protection**
Password protection for the logs folder based on ".htaccess" (on | off).
This is enabled by default. It is strongly recommended to keep .htaccess protection enabled. Without proper protection
your logs might be easily accessible to others because typicially for a free hosting account you don't have access to folders above your document root so logs are stored relative to the document root of your webspace.
If you use a password-like naming scheme for your "logs_path" and "base_name" you might consider to disable  .htaccess protection.
FLW will handle the creation and alteration of all neccessary files for .htaccess protection for you.

#### Individual logging for multiple applications 
This feature is available if multiple settings files like this one are configured.

To do so you have to provide individual .ini file(s) in the same folder as the default .ini file and adhere to the
following naming scheme: *"FLW-yourname.ini"* for the settings and (if "htaccess_protection" is on) *"FLW-yourname-cred.ini"*
for the user/password credentials. To have the log writer read your .ini file (instead of the default file), initialize 
the writer with "yourname" as a parameter (don't append the .ini extension).
	
#### FLW credentials file format (FLW-default-cred.ini.example):

	user = "testuser"
	password = "plainTextPassword"
	
This ini file is used to configure the user and password for .htaccess INITIALLY ONLY.
When the logwriter is called for the first time and this ini file exists and is valid, a fresh set of 
.htaccess and .htpasswd files will be created in the specified path (logs_path) where the password will be 
saved as a hash and the credentials ini file will then be DELETED for security reasons. The logwriter will not replace 
.htaccess/.htpasswd until it finds a new valid credentials ini file.
Please note that "password" must at least be 8 characters long.
	
<br/>	
	
<a name="csv_data"></a>	
### Writing CSV data [(^)](#_top)

With the help of the *"FileLogWriterHelper"* class you might store your log data in CSV format (if you are into weird things :)).

First thing to do after the logger's *log* method has done its job is to prepare your log data. *"content2CSV"* accepts any associative array with a field name for a key and field content as a value (the logger content object offers many different methods to provide such arrays):

	$csv_data= FileLogWriterHelper::content2CSV($logger->getLoggerContent()->toArray(), $csv_params);
	
*$csv_params* might be provided as follows (defaults will be used for omitted parameters or if no parameters are provided at all):

	$csv_params= array(
		"field_delimiter" => ';',	// default: ,
		"field_quote" => '\'',		// default: "
		"field_quote_escape" => '\\'	// default: "
	);

If you want to have (more descriptive) captions instead of (more cryptic) field names in the header of your CSV file, this can be done by a helper method of your content object:

	$content= $logger->getLoggerContent()->toArray();
	$content= $logger->getLoggerContent()->fieldNames2Captions($content);
	$csv= FileLogWriterHelper::content2CSV($content, $csv_params);

*Note:* If a field has no caption set, the field name is used (so for log formats without any caption the array basically stays the same ;)).
	
Because a CSV file is supposed to have a header (1st line) where all fields are listed, actually writing your log data to a file is best done with *"FileLogWriterExt"* which provides a special method for that purpose:
	
	$writer= new FileLogWriterExt();
	$writer->writeToLogExt($csv["field_content"], $csv["field_names"]);

<br/>	
	
<a name="db_writer"></a>	
### The database log writer (DBLW) [(^)](#_top)

DBLW is based on the PDO-compliant [Doctrine 2 Database Abstraction Layer (DBAL)](http://www.doctrine-project.org/index.html) to provide a vendor independent interface to the most popular relational database servers (also support for No-SQL approaches like MongoDB seems to be in sight with the Doctrine project).

#### A brief overview on how the Doctrine DBAL setup is handled in this project
The usual and most straight-forward approach to including this API in a project would be by using *"Composer"*. All you have to do then is to include the autoloader script:

	require __DIR__.'/../../vendor/autoload.php';
	
The problem with most free webhosting accounts is: You don't have access to a shell, you just can't utilize tools like Composer. But there's always the (slightly more inconvenient) workaround to install things yourself. This is the way DBLW works internally.

This version of DBLW comes with everything in place, "pre-installed" and ready to go. If Doctrine DBAL gets an update, so will this little project shortly after (hopefully :)). But *IF* the "hopefully part" won't work so well or you might want to mess around with (another version) of DBAL yourself, here's a little info on what to do... oh and keep in mind, we're using version "2.5.4" in this example, so you might want to adjust this (and maybe any other parts of the path) to your needs...

#### Installing and using Doctrine 2 DBAL without the help of *"Composer"*

Just download the compressed archives for the ["DBAL"](http://www.doctrine-project.org/projects/dbal.html) and ["Common"](http://www.doctrine-project.org/projects/common.html) stuff from the Doctrine website. Then take the "Common" folder (your_path_to_common_folder/lib/Doctrine/Common) where you extracted the downloaded zip file to 
and copy it right into the DBAL package folder relative to "your_path_to_dbal_folder/lib/Doctrine/" so in the end you have the following folder structure:

	/your_document_root/your_project_folder/your_path_to_dbal_folder/
	lib
	  | Doctrine
		| Common
		| DBAL
	
In the end it is all a matter of a slightly more complex initial code up-front, for example:
	
	use Doctrine\Common\ClassLoader;
	define('DBAL_BASE_PATH', __DIR__.'/../../dbal-2.5.4/');
	require DBAL_BASE_PATH.'lib/Doctrine/Common/ClassLoader.php';
	$classLoader= new ClassLoader('Doctrine', DBAL_BASE_PATH.'lib');
	$classLoader->register();
	
But don't fret, the DBLW reference implementation takes care of all the stuff mentioned above and will include and initialize Doctrine DBAL for you.

#### Prepare the database log writer

DBLW's constructor takes two arguments: a set of connection parameters for Doctrine to connect to your database (mandatory) and a set of parameters for the writer itself (optional):

	$connectionParams= array(
		'dbname' => 'test',
		'user' => 'test',
		'password' => 'test',
		'host' => 'localhost',
		'port' => 3306,
		'charset' => 'utf8',
		'driver' => 'mysqli',
	);
	
You might use all parameters supported by Doctrine DBAL in the intended way. It's up to you to fill them in so they make sense (connection errors from DBAL will be stored in your writer's "error" property).

You might want to apply parameters for the writer itself:	
	
	$writerParams= array(
		// name of the log table 
		//(must be all lowercase and a-z and underscore only, otherwise non-compliant characters will 
		// be sifted out and a warning occurs)
		'table' => "access_log",
		// if fields are discontinued or datatypes change, never drop or alter existing fields in the db
		'safety' => DatabaseLogWriterHelper::SAFETY_MAX,
		// apply custom datatype mappings via data array
		'datatype_mappings' => array(),	// for details see "Custom datatype mappings" below
		// replace internal defaults entirely ("true") or make up/overwrite them ("false")
		'datatype_mappings_replace_defaults' => false,
		// Strategy for safe naming of database tables and columns:
		// SAFE_NAMING_STRATEGY_WTL_CLEANSING (default): phpWTL internal character filter
		// SAFE_NAMING_STRATEGY_DBAL_ESCAPING: Doctrine DBAL escaping via "quoteIdentifier" method
		'safe_naming_strategy' => DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING
	);

Above you see an example of all parameters available and their defaults.

#### Safety levels (as defined in *"DatabaseLogWriterHelper"*)

| Value             			| Description 																						|
|-------------------------------|---------------------------------------------------------------------------------------------------|
| SAFETY_MAX, SAFETY_ALL (*)	| All safety options set, don't drop or change fields in the database ever							|
| SAFETY_DROP					| Drop fields in the database if they are discontinued, but don't change altered fields				|
| SAFETY_CHANGE					| Change fields in the database if type and/or attributes change, but don't drop omitted fields		|
| SAFETY_NONE, SAFETY_OFF		| Safety completely disabled (most flexible and clean but data loss might occur)					|
<sub>(*) default</sub>	

#### Custom datatype mappings 

Custom datatype mappings can be defined in form of an associative array assigned to the *"datatype_mappings"* parameter. *"datatype_mappings_replace_defaults"* controls if those mappings make up/overwrite internal defaults ("false") or replace them entirely ("true"), so single default datatypes might get purged.

The *"DatabaseLogWriterHelper"* class provides a method to read mapping parameters from an .ini file and return them as an array to feed to the DBLW's constructor (*$writerParams*):

	$datatypeMappings= DatabaseLogWriterHelper::getDatatypeMappingsFromIni($inifile);
	
Ini files have to be located in the same folder as DBLW. Per default (if you omit the *$inifile* parameter) all mappings are read from the default ini file *"DBLW-DatatypeMappings.ini"*. *"$inifile*" might be given with or without the ".ini" extension.

Here's a structural example of a custom datatype mappings file:

	[custom_type_1]
		dbal_type= 'doctrine_datatype'
		alias[]= 'alternative_name'
		alias[]= '...'
		option[key_1]= 'value'
		option[key_2]= '...'
	[custom_type_2]
		dbal_type= 'doctrine_datatype'
		...
	...	
	
Which as an array would look like this:

	array (
		'custom_type_1' => array (
			'dbal_type' => 'doctrine_datatype',
			'alias' => array (
				'alternative_name', '...'
			),
			'option' => array (
				'key_1' => 'value',
				'key_2' => '...'
			)
		),
		'custom_type_2' => array (
			'dbal_type' => 'doctrine_datatype'
		)
	)	
	
| Field (array key)    			| Cardinality | Description 															 |
|-------------------------------|-------------|--------------------------------------------------------------------------|
| custom_type                   | [1..n]      | phpWTL datatype name (definition group), mandatory					     |
| dbal_type                     | [0..1]      | Doctrine DBAL datatype name, optional (same as "custom_type" if omitted) |
| alias[]                       | [0..n]      | alternative phpWTL datatype name(s), optional						     |
| option[key]                   | [0..n]      | Doctrine DBAL default portable options (key-value pairs), optional       |


#### Connection parameters in an external .ini file

Connection parameters might be transferred to a separate .ini file which can be better secured using *.htaccess*: 

	dbname 		= 'test'
	user 		= 'test'
	password 	= 'test'
	host 		= 'localhost'
	port 		= '3306'
	charset 	= 'utf8'
	driver 		= 'mysqli'

The *"DatabaseLogWriterHelper"* class provides a method to read those parameters from an .ini file and return them as an array you can then feed to DBLW's constructor (*$connectionParams*):

	$connectionParams= DatabaseLogWriterHelper::getConnectionParamsFromIni($handle_htaccess, $inifile);
	
Ini files have to be located in the same folder as DBLW. Per default (if you omit the *$inifile* parameter) all connection parameters are read from the default ini file *"DBLW-ConnParam.ini"*. *"$inifile*" might be given with or without the ".ini" extension.

The *$handle_htaccess* parameter might be set to "true" if you want .htaccess protection for your .ini file (this might also be done manually with the help of the *"prepareHtaccessProtection"* method):
	
	<Files "DBLW-ConnParam.ini">
		Order Allow, Deny
		Deny from all
	</Files>

If a .htaccess file exists already and there's no \<File\> section for the specified ini name in place yet, the lines above will be appended. Otherwise a new .htaccess file including the lines above will be created.
	
#### Naming conventions for database tables and columns

While Doctrine DBAL does have a vendor-independent strategy in place to deal with naming conventions, it is disabled by default. The Doctrine API reference itself states that escaping/quoting across different database platforms is "a very tricky business" and "just because you CAN use quoted identifiers does not mean you SHOULD use them". 

And in fact: **It doesn't work sufficiently with the DBAL schema manager and is inconsistent and buggy!** For example the *"addColumn"* method will accept properly escaped strings (via *"quoteIdentifier"* method) for a name, while methods like *"dropColumn"* will not (they un-escape/normalize the given string internally). The outcome is a totally inconsistent SQL for a schema migration. I've tried to address this faulty DBAL behavior by altering the schema SQL in retrospect. It works fine with MySQL but I have no way and intentions to test this fix extensively on other database platforms/configurations.
Therefore the decision was made to adhere to the least common denominator for a string used as a database table/column name by default (i.e. remove all characters other than a-z and underscore and convert to lowercase).
Nevertheless you CAN override this and use DBAL escaping instead...maybe it'll work just fine with your specific setup (*"safe_naming_strategy"* key in *"$writerParams"*, see section above). But I strongly won't recommend it!

#### Using the database log writer
	
In order to get the raw datatype right you need to disable the formatter. Then the logger will automatically set the "*datatype class*" in your logger content object to *"DATATYPE_RAW"* (see the pre-defined constants in the *FormatDescriptorHelper* class). This could be done manually, too (possible constants are "*DATATYPE_RAW*" and "*DATATYPE_FORMATTED*"):

	$logger->getLoggerContent()->setDatatypeClass(FormatDescriptorHelper::DATATYPE_RAW);

You retrieve data later on with one of the "*toArrayTyped()*" methods which adhere to the datatype class set before. If the formatter is enabled, the datatype will match the formatted type (*FormatDescriptorHelper::DATATYPE_FORMATTED*):

	// Disable formatter, retrieve field data with information about the *raw* type.
	// Use this way to log a format like "*Combined*" to a database in a more datatype-oriented way 
	// (e.g. an unformatted timestamp value will be stored in a "datetime" field).
	$logger->log(array("format" => false));
	$logger->getLoggerContent()->toArrayTyped();

	// Enable formatter, retrieve field data with information about the *formatted* type
	// Use this way to log a format like "*Combined*" to a database in a strict format-conform way 
	// (e.g. a formatted timestamp value will be stored in a "string" field).
	$logger->log(array("format" => true));
	$logger->getLoggerContent()->toArrayTyped();
	
<small>(Info: Feel free to retrieve data stripped from delimiters from the logger content object through one of the *toArrayTypedTrimmed()* methods.)</small>
	
	
Now it's time to instantiate the writer:	
	
	$writer= new DatabaseLogWriter($connectionParams, $writerParams);

Use the *"writeToLog"* method to write your log data to the database. It takes two parameters, one associative array containing the field data and a second one (optional) containing data for meta fields (which are stored in a separate table and unlike the main logging table data won't be appended but updated). Both arrays need to have a special format which looks something like this:

	$example_array= array ("example_field_name" => array (
		"datatype" => "example_type",
		"content" => "example_content"
	));

Your logger content object provides methods to retrieve arrays in this format. Here's a more complex example:

	$keys_to_show= $logger->getFormatDescriptor()->getRegularFieldNames();
	$key_to_delete= array_search("cs-username", $keys_to_show);
	if ($key_to_delete) unset($keys_to_show[$key_to_delete]);
	$keys_to_show= array_reverse($keys_to_show);
	$content_obj= $logger->getLoggerContent();
	$writer->writeToLog(
		$content_obj->toArrayTyped($keys_to_show), 
		$content_obj->toArrayTypedMeta()
	);

The following methods might be used to obtain data for a database log writer like DBLW:

* **toArrayTyped()** Obtain non-meta fields flagged as default in regular order or fields and order provided via *"$whitelist"* parameter
* **toArrayTypedMeta()** Obtain meta fields only, flagged as default in regular order or fields and order provided via *"$whitelist"* parameter
* **toArrayTypedTrimmed()** Obtain non-meta fields flagged as default in regular order or fields and order provided via *"$whitelist"* parameter, trim content (strip prefix and suffix) - this might be needed to log data "type-safe" from delimited fields to a database.
* **toArrayTypedMetaTrimmed()** Obtain meta fields only, flagged as default in regular order or fields and order provided via *"$whitelist"* parameter, trim content (strip prefix and suffix) - see above

All methods above take a *"$whitelist"* as an (optional) parameter.
	
#### The W3C Extended log file format special treatment

Here's an example for the W3C Extended logger (class *"ExtendedLogger"*). There's special meta data (so called "directives") which need a little extra care ;-)	

	$keys_to_show= $logger->getFormatDescriptor()->getRegularFieldNames();
	$key_to_delete= array_search("cs-username", $keys_to_show);
	if ($key_to_delete) unset($keys_to_show[$key_to_delete]);
	$keys_to_show= array_reverse($keys_to_show);

	// Update Extended format's "fields" directive
	$logger->setDirectives($keys_to_show);

	// Don't update Extended format's "start-date" directive in the DB
	$meta= $writer->fetchMetaDataFromDB();
	if ($meta) $logger->getDataRetriever()->setFieldContent("dir_start-date", $meta["dir_start-date"]);
	
	$content_obj= $logger->getLoggerContent();
	$writer->writeToLog(
		$content_obj->toArrayTyped($keys_to_show), 
		$content_obj->toArrayTypedMeta()
	);
	
<br/>

<a name="php_logger"></a>
## PHP application event logging [(^)](#_top)

If you need to log events, errors or exceptions of your PHP script(s), the *PhpAppLogger* class is there for you. Here's a brief but ample example which I'll go to explain in detail below:

	$logger= PhpAppLogger::getInstance(PhpAppLoggerHelper::LOGLEVEL_WARNING);
	$params= array(
		"loglevel" => PhpAppLoggerHelper::LOGLEVEL_ERROR,
		"message" => "Hello world! This logger has a {format_prefix} format prefix. This is call {count}.",
		"context" => array(
			"exception" => new RuntimeException('Hey dude, things went south BIG time!'),
			"format_prefix" => $logger->getFormatDescriptor()->getformatPrefix(),
			"count" => 1,
			"nested_data" => array(
				"one" => 1, 
				"two" => array("hello" => "2_1", "world" => "2_2"), 
				"three" => 3
			),
		),
		"exclude_placeholders_from_context" => true
	);
	$fail= $logger->log($params);
	if (!$fail) print_r($logger->getLoggerContent()->toArray()); else print_r($fail);

*PhpAppLogger*'s constructor takes the loglevel threshold as an optional argument (default is *LOGLEVEL_WARNING*). If the *log()* method is called with a level above the threshold, no logging will be done and an error (array) will be returned respectively. Also the logger content object will be detached from the logger object until a subsequent call to *"log()"* succeeds. The following loglevels are defined in the *PhpAppLoggerHelper* helper class based on RFC 5424 (in order of increasing severity):

	LOGLEVEL_DEBUG 
	LOGLEVEL_INFO 
	LOGLEVEL_NOTICE 
	LOGLEVEL_WARNING 
	LOGLEVEL_ERROR 
	LOGLEVEL_CRITICAL 
	LOGLEVEL_ALERT 
	LOGLEVEL_EMERGENCY

The actual values of loglevels correspond with RFC 5424 (the lower the more severe) but were all multiplied by 100 to allow for custom loglevels in-between in future implementations.

The *log()* method takes parameters in form of an array, where *loglevel* and *message* are mandatory to make the logging meaningful:
	
* **loglevel**: Severity level of the event to log. A level caption will be logged to the *loglevel_caption* field, while the loglevel's numerical value will be logged to the *loglevel* field. Captions are defined as an array in *PhpAppLoggerHelper::$LOGLEVEL_CAPTION*, with a loglevel constant as the key).
* **message**: Actual string to write to the log (*message* field). Variables might be embedded into the string (context data). Placeholders must be delimited with curly braces like this: {variable}. Nested array data won't be processed recursively and actually not embedded at all (data has to be a scalar/string or an object with a *__toString()* method).
* **context**: Data array containing key-value pairs of data meant to hold any extraneous information that does not fit well in a string. This is meant to be logged to the *context_data* field. Context might contain anything type-wise and will be treated with as much lenience as possible. In the end all data will be processed as proper JSON. If context contains a scalar/string type or an object with a *__toString()* method its respective value will be processed properly. In addition nested array data will be processed recursively.
<br/><u>**Exceptions**:</u> If an Exception object is passed in the context data, and this is done in the 'exception' key, much more information of it will be resolved as its *__toString()* representation alone (you might obtain this information yourself, using the public method *"e2arr"* from the logger). A full JSON representation of the given exception object will be built, keys analog to the corr. methods of the exception object: eMessage, ePrevious, eCode, eFile, eLine, eTrace, eTraceAsString, eToString.
* **exclude_placeholders_from_context**: By default context data with a corresponding placeholder in the message string won't be (redundantly) logged to the *context_data* field. You can change this behavior if you want.	
	
<br/>

<a name="log_buffer"></a>
## Buffered logging [(^)](#_top)

Log entries can be aggregated using the *"LogBuffer"* wrapper class. Logging content will be stored in a buffer until the (adjustable) buffer size is reached or the *flush()* method is called explicitly. 

	$logger= PhpAppLogger::getInstance();
	$writer= new FileLogWriterExt();
	$logbuffer= new LogBuffer($logger, $writer, $myCallbacks, $params);

*"$myCallbacks"* is meant to provide optional and also **mandatory** callback methods for the log buffer class to operate properly. *"$params"* is optional and might contain parameters for the buffer to override defaults:

	$params= array (
		// amount of content objects (=log entries or log events) to be buffered 
		// before flush() will be called automatically
		// (set to LogBufferHelper::BUFFER_OFF to disable buffering)
		'buffer_size' => 20;
	);

	$myCallbacks= array (
		LogBufferHelper::CALLBACK_FLUSH_EACH => "mySimpleFlush"
	);
	
*"LogBufferHelper::CALLBACK_FLUSH_EACH"* defines how to use and feed the associated writer during the buffer flush loop (i.e. store a log entry from a single logger content object). This is neccessary because potentially there are many different writers with different approaches how to write stuff and what information is needed to do so:

	function mySimpleFlush($writer_object, $content_object) {
		$writer_object->writeToLog($content_object);
	}

Or for a more complex example. You probably might want to treat contents in a special way before actually storing it (here: CSV data). So your callback function acts like a hook method:

	function myComplexFlush($writer_object, $content_object) {
		$csv_params= array(
			"field_delimiter" => ';',
			"field_quote" => '\'',
			"field_quote_escape" => '\\'
		);
		$content= $content_object->toArray();
		$content= $content_object->fieldNames2Captions($content);
		$csv= FileLogWriterHelper::content2CSV($content, $csv_params);
		$writer_object->writeToLogExt($csv["field_content"], $csv["field_names"]);
	}

The log buffer basically can be operated in two ways: Either it performs the actual logging itself (*log()* method) or it is provided with a content object which comes from a logger's *getLoggerContent()* method (*store()* method). The main difference is, if you intend to alter contents (of the logger content object) after logging you'll have to use *store()* and trigger your logger's *log* method yourself beforehand or provide the *log()* method of your LogBuffer with a callback function.

First an exampel of a simple *"log"* method:

	$logbuffer->log($my_params);

Now (a more complex and lengthy) example for the *"store"* method:

	$logger->log($my_params);
	$myval= "hello world!";
	if ($logger->getDataValidator()->isValid("message", $myval)) {
		$logger->getDataRetriever()->setFieldContent("message", $myval);
		$logger->getDataFormatter()->formatAllField("message");
	}
	$logbuffer->store($logger->getLoggerContent());
	
If you provide the *LogBuffer* constructor with a callback function (e.g. *"LogBufferHelper::CALLBACK_LOG_AFTER"*), you might as well have the *log()* method perform the task above (which method you use is but a matter of personal taste :)):

	function myLogAfter($content_object) {
		$myval= "hello world!";
		if ($logger_object->getDataValidator()->isValid("message", $myval)) {
			$logger_object->getDataRetriever()->setFieldContent("message", $myval);
			$logger_object->getDataFormatter()->formatAllField("message");
		}
	}
	
	$myCallbacks= array (
		LogBufferHelper::CALLBACK_LOG_AFTER => "myLogAfter",
		LogBufferHelper::CALLBACK_FLUSH_EACH => "myFlushEach"
	);
	
	$logbuffer= new LogBuffer($logger, $writer, $myCallbacks, $params);	
	// ...
	$logbuffer->log($my_params);
	
If the buffer size limit is reached any subsequent call to *log()* or *store()* will invoke the *flush()* method (and store the whole buffer via the associated writer using the provided callback class). You might as well call *flush()* anytime manually.	
	
<br/>

<a name="apidoc"></a>
## API documentation [(^)](#_top)

This is powered by *phpDocumentor*, auto-generated based on all those fancy DocBlock/PHPdoc annotations the sourcecode is riddled with. [A snapshot can be browsed here](http://127.0.0.1/lab/phpWTL/docs/api/namespaces/phpWTL.html). There's also a brief overview of the [class hierarchy](http://127.0.0.1/lab/phpWTL/docs/api/graphs/class.html).
<br/>
_BTW: This project adheres to [semantic versioning](http://semver.org/)._
	
<br/>

<a name="formats"></a>
## Log formats supported by phpWTL [(^)](#_top)

As of this release phpWTL supports the following major log formats:	
	
		
#### NCSA common log format	

* Commonly used standard for webservers
* **Apache** uses the common log format as a default
* [Further information: https://httpd.apache.org/docs/2.4/logs.html#accesslog](https://httpd.apache.org/docs/2.4/logs.html#accesslog)
* List and sequence of field IDs:
	- host_ip, client_identity, user_id, timestamp, request_line, status_code, content_size
* Technical limitations in the support of the following fields:
	- *client_identity* This always returns a hyphen. Even Apache does not try to obtain data here (it is regarded "highly unreliable").
	- *status_code* Might not always be set properly because a PHP script is not in a webserver's position
	- *content_size* [This is discussed en detail right here](#drawback_2)

**Overview**

| Field             							| ID              | Default | Supported | Datatype raw (formatted)	 | Formatter 			| Delimiter (prefix/suffix) |
|-----------------------------------------------|-----------------|:-------:|:---------:|----------------------------|----------------------|---------------------------|
| Client IP Address		 						| host_ip         |    X    |     X     | string	 				 |						|	   		 			    |
| Client Identity		 						| client_identity |    X    |    (X)    | string	 				 |						|	   	 				    |
| User Name				 						| user_id         |    X    |     X     | string	 				 |						|	   					    |
| Date and Time of Request						| timestamp       |    X    |     X     | datetime (string)			 | %d/%b/%Y:%H:%M:%S %z | [ and ]					|
| Request Line (Method, URI+Query, Protocol)	| request_line    |    X    |     X     | string	 				 |						| both double quotes		|
| HTTP Status			 						| status_code     |    X    |    (X)    | integer	 				 |						|							|
| Bytes sent			 						| content_size    |    X    |    (X)    | integer (string)			 | %b					|							|

	
<br/>	
	
#### Combined log format	

* Wide-spread extension of the Common log format
* Offers two additional log fields
* Used by **Apache** and by **Nginx** as a default
* [Further information: https://httpd.apache.org/docs/2.4/logs.html#accesslog](https://httpd.apache.org/docs/2.4/logs.html#accesslog)
* List and sequence of field IDs:
	- host_ip, client_identity, user_id, timestamp, request_line, status_code, content_size, referrer, user_agent
* Technical limitations are basically the same as with the Common format

**Overview (additional fields to Common)**

| Field             							| ID              | Default | Supported | Datatype raw (formatted)	 | Formatter 			| Delimiter (prefix/suffix) |
|-----------------------------------------------|-----------------|:-------:|:---------:|----------------------------|----------------------|---------------------------|
| referrer				 						| referrer        |    X    |     X     | string	 				 |						| both double quotes		|
| User Agent			 						| user_agent	  |    X    |     X     | string	 				 |						| both double quotes		|


<br/>	
	
#### W3C Extended log file format	

* Commonly used by Windows servers (IIS)
* Offers a bit more flexibility than Common or Combined
* [Further information: https://www.w3.org/TR/WD-logfile.html](https://www.w3.org/TR/WD-logfile.html)
* List and (default) sequence of field IDs supported:
	- date, time, c-ip, cs-username, s-computername, s-ip, s-port, cs-method, cs-uri-stem, cs-uri-query, sc-status, sc-win32-status, sc-bytes, time-taken, cs-version, cs-host, cs-user-agent, cs-cookie, cs-referrer, sc-substatus
* Fields contained for future extensibility but currently unsupported (retriever will return a hyphen):
	- *sc-substatus* Might be utilized to contain error codes of any kind
* Supported directives (# lines):
	- Version, Fields, Start-Date, Software, Remark
* Fields NOT supported (due to technical or logical limitations):
	- s-sitename, sc-win32-status, cs-bytes
* Technical/logical limitations of the following supported fields:
	- *sc-status* Might not always be set properly because a PHP script is not in a webserver's position
	- *sc-bytes* [This is discussed en detail right here](#drawback_2)
	- *time-taken* Measurement by PHP script can only be an approximation

**Overview**

| Field             				| ID              | Default | Supported | Datatype raw (formatted)	| Formatter 			|
|-----------------------------------|-----------------|:-------:|:---------:|---------------------------|-----------------------|
| Date           					| date            |    X    |     X     | date						| %Y-%m-%d				|
| Time              				| time            |    X    |     X     | time						| %H:%M:%S				|
| Client IP Address 				| c-ip            |    X    |     X     | string					|						|
| User Name         				| cs-username     |    X    |     X     | string					|						|
| Service Name and Instance Number  | s-sitename      |         |     -     | 							|						|
| Server Name       				| s-computername  |         |     X     | string					|						|
| Server IP Address 				| s-ip            |    X    |     X     | string					|						|
| Server Port       				| s-port          |    X    |     X     | integer					|						|
| Request Method    				| cs-method       |    X    |     X     | string					|						|
| Base URI          				| cs-uri-stem     |    X    |     X     | string					|						|
| URI Query String  				| cs-uri-query    |    X    |     X     | string					|						|
| HTTP Status       				| sc-status       |    X    |    (X)    | integer					|						|
| Win32 Status      				| sc-win32-status |         |     -     | 							|						|
| Bytes sent        				| sc-bytes        |         |     X     | integer					|						|
| Bytes received    				| cs-bytes        |         |     -     | 							|						|
| Time Taken        				| time-taken      |         |    (X)    | integer					|						|
| Protocol Version  				| cs-version      |         |     X     | string					|						|
| Host              				| cs-host         |         |     X     | string					|						|
| User Agent        				| cs-user-agent   |    X    |     X     | string					|						|
| Cookie            				| cs-cookie       |         |     X     | string					|						|
| Referrer          				| cs-referrer     |         |     X     | string					|						|
| Protocol Substatus				| sc-substatus    |    X    |    (X)    | string					|						|
<small>
(Default = IIS default settings)
</small>	

**Directives (meta fields)**

| Field             				| ID              | Default | Supported | Datatype raw (formatted)	| Formatter 			|
|-----------------------------------|-----------------|:-------:|:---------:|---------------------------|-----------------------|
| #Version           				| dir_version     |    X    |     X     | string					| 						|
| #Fields           				| dir_fields      |    X    |     X     | string					| 						|
| #Start-Date           			| dir_start-date  |    X    |     X     | datetime					| %Y-%m-%d %H:%M:%S		|
| #End-Date     	      			| dir_end-date    |         |     -     | 							| 						|
| #Date  		   	      			| dir_date	      |         |     -     | 							| 						|
| #Software           				| dir_software    |    X    |     X     | string					| 						|
| #Remark           				| dir_remark      |    X    |     X     | string					| 						|

Note: W3C Extended fields have no delimiters.
	
<br/>

#### PhpAppLogger	

A simple and (phpWTL) proprietary format to log application events, errors and exceptions.
	
| Field             						| ID               | Datatype raw (formatted)	| Formatter 			|
|-------------------------------------------|------------------|----------------------------|-----------------------|
| date and time        						| timestamp    	   | datetime					| %Y-%m-%d %H:%M:%S		|
| string to log        						| message	       | string						|						|
| loglevel value (RFC 5424 x 100)    		| loglevel	       | integer					|						|
| loglevel string representation 	  		| loglevel_caption | string						|						|
| additional data (e.g. exceptions), json	| context_data     | text						|						|

	
<br/><br/>

<a name="contributing"></a>
## Contributing to this project [(^)](#_top)

Because this is work in progress and above all in a really early state, I'm currently not interested in the contribution of others in form of pull requests or the like. But this doesn't mean, I won't acknowledge any feedback or suggestions. Oh, and of course bug reports... in this state the project must be full of them! No, in fact, those are highly welcome and appriciated. I just don't want others mess with the code or concept yet, at least as long as there's still no real stable release. I still have many ideas and notions myself and want the very foundation of this project to be ready before I start thinking about integrating stuff from other developers.
<br/>
But feel free to drop me a line. Either here on GitHub or via Email.

<br/>

<a name="license"></a>
## License [(^)](#_top)

Copyright 2016 Michael G. Beyer (<mgbeyer@gmx.de>).

Licensed under the Apache License, version 2.0.
<br/>
[(http://www.apache.org/licenses/LICENSE-2.0)](http://www.apache.org/licenses/LICENSE-2.0)

<br/>

## And now for all the lengthy stuff which sort of was getting in the way in all the sections above... [(^)](#_top)
<a name="drawback_1"></a>
[(^)](#drawbacks)
**Logging of collateral files**
The problem applies to all sorts of static assets your PHP script/application is not directly involved in their loading, like images, stylesheets or JavaScript files. Unless you actively take care they somehow will be included in the logging process they simply won't show up in your logs. phpWhatTheLog has means and capabilities to assist you with this task (by defining so-called "data retrieval policies" and by altering single log fields in retrospect). But you have to put some extra effort into this, it won't work out-of-the-box just by including and invoking the logger. In a later section of this manual I'll discuss strategies how to e.g. log image files.	

<a name="drawback_2"></a>
[(^)](#drawbacks)
**Logging "content-length"**
For a webserver this value is easy to retrieve, the output is sent to it *afterwards* so it is in the best position to actually *see* the size. But all we can do within the limited world of our little PHP script is a) to try and estimate or b) really measure. phpWhatTheLog does the former by default: It just looks how big the filesize of the PHP script is, you include the logger. This *might* come close to the real output produced by the script but also might differ big time (largely depending on your code). But you can choose to configure the latter method: Actually *measure* the size of your script's output. When this is set, PHP output control (buffering) will be used to actually calculate the size of content produced by the script and sent back to the client. This might not be exactly the value a webserver would see and may be off by a couple bytes due to measurement/script-injection logistics but it comes pretty close to a real webserver log entry. The main drawback of this method is that now the whole output of the script will be buffered. While output buffering is active no output is sent from the script (other than headers), instead the output is stored in an internal buffer until the "log" method is called (at this point content-length will be measured and the whole buffer will be sent "en bloc"). Most of all this means if possible you should instantiate your logger right at the beginning of your script (this will start the buffer) but call the "log" method not before the end of your script (which will terminate and send the buffer). Whatever output is produced before the instantiation and after calling "log" will not be measured.
