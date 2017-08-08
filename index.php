<?php
/**
*	Site Map Generator
*	
*	A simple script that parses a website to generate an XML Site Map
*	URL's are parsed in a Breadth-First search manner
*	
*	**potential bug: detects the same page twice when the trailing slash makes a difference
*	 - caching?
*/
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Site Map Generator</title>
	</head>
	<body>
		<div id="content">
			<h1>Site Map Generator</h1>
			<form action="index.php" method="POST">
				URL: <input name="url" placeholder="http://www.example.com"/>
				<input type="submit" name="submit" value="Start Crawling"/>
			</form>
			<?php
				//error_reporting(E_ALL);
				//ini_set('display_errors', '1');

				/**
				*	Change a relative URL to an absolute URL based on the provided base URL
				*
				*	@param string $rel		Relative URL to be converted
				*	@param string $base		Base URL of which the relative URL should be contained
				*	@return (Scheme of Base . :// . constructed absolute URL)
				*/
				function rel2abs($rel, $base)
				{
					// Return if already an absolute URL */
					if (parse_url($rel, PHP_URL_SCHEME) != '') return ($rel);

					// Return for queries and anchors
					if ($rel[0] == '#' || $rel[0] == '?') return ($base . $rel);

					// If the relative URL does not begin with slashes or decimals, indicate to look in current directory
					if (substr($rel,0,1) != '/' && substr($rel,0,2) != '//' && substr($rel,0,2) != './' && substr($rel,0,3) != '../') $rel = "./" . $rel;

					// Parse base URL and convert to local variables: $scheme, $host, $path, $query, $port, $user, $pass
					extract(parse_url($base));

					// If path did not exist
					if (!isset($path)) $path = '';
					
					// Remove non-directory element from path (e.g "/bar" in "/foo/bar")
					$path = preg_replace('#/[^/]*$#', '', $path);

					// Destroy 'path' if relative URL points to root
					if ($rel[0] == '/') $path = '';

					// Absolute URL
					$abs = '';

					// Check if user is set
					if (isset($user))
					{
						$abs .= $user;

						// Check if password is set
						if (isset($pass)) $abs .= ':' . $pass;

						$abs .= '@';
					}
					
					// Add host
					$abs .= $host;

					// Construct current absolue URL; Check if 'port' and 'query' is set
					$abs .= (isset($port) ? ':' . $port : '') . $path . '/' . $rel . (isset($query) ? '?' . $query : '');

					// Replace occurrences of '//' or '/./' or '/foo/../' with '/' 
					$re = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
					for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {}

					// Add scheme and return
					return ($scheme . '://' . $abs);
				}
				
				/**
				*	Check whether or not a URL is valid using the web page response code. 
				*
				*	@param string $str		A URL or HTTP Header to be evaluated
				*	@param bool $bl			Toggle indicating if $str is A URL (0) or HTTP Header (1)
				*/
				function validUrl($str, $bl)
				{	
					// If file_get_contents() is called, result of $http_response_header[0] is passed
					if ($bl) return ($str != 'HTTP/1.1 404 Not Found' && $str != 'HTTP/1.1 410 Gone');

					// If not, a URL is passed and get_geaders() is called on a passed URL
					else
					{
						// @ to supress XHTML parding errors
						$file_headers = @get_headers($str);

						return !(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found' || $file_headers[0] == 'HTTP/1.1 410 Gone');
					}
				}
				
				/**
				*	Parse the contents of a webpage and collect all valid 'hrefs'
				*
				*	@param string $url							URL to parse
				*	@param array (associative) &$crawled_urls	Valid URL's that have been crawled (passed by reference)
				*	@param array (indexed) &$found_urls			Collection of URL's found on webpages throught the website
				* 	@param int &fnum							Maintained index of last $found_urls item  
				*/
				function crawl_site($url, &$crawled_urls, &$found_urls, &$fnum)
				{
					if(!array_key_exists($url, $crawled_urls))
					{
						//Create a new DOM document
						$dom = new DOMDocument;
						
						//Parse the passed URL. 
						// @ supresses parsing errors for invalid XHTML elements. 
						@$dom->loadHTML(file_get_contents($url));

						if(validUrl($http_response_header[0], 1))
						{
							// URL assined as key to create hash table; junk value given
							$crawled_urls[$url] = 1;
							
							// Collect all anchor tags on the webpage
							$links = $dom->getElementsByTagName('a');
						
							// Iterate over the extracted anchor tags and display their URLs
							foreach ($links as $link)
							{
								// Get 'href' and host component of anchor
								$curLink = $link->getAttribute('href');
								$host = parse_url($curLink,PHP_URL_HOST);
								
								// Only parse links within the current domain
								if ($curLink != '' && ($host == null || ($host != null && $host == parse_url($found_urls[0],PHP_URL_HOST))))
								{
									// Reconstruct URL by concatenating the domain being parsed, and the path of the absolute 
									//     URL of the current 'href'
									// Root URL containded in the first entry of $found_urls
									$furl = $found_urls[0] . ltrim(parse_url(rel2abs($curLink, $url),PHP_URL_PATH) , '/');
									
									// Perfomance better than using array_push()
									if(!array_key_exists($furl, $crawled_urls)) $found_urls[++$fnum] = $furl;
								}
							}
						}
					}
				}

				if(isset($_POST['submit']))
				{
					$url=$_POST['url'];
					
					// The 'http' scheme is added to the passed URL by default if a scheme is not present
					// If a scheme is present, then the domain is in PHP_URL_HOST; Otherwise the domain is in PHP_URL_PATH
					// 		it is more beneficial to have a scheme present
					if (parse_url($url, PHP_URL_SCHEME) == null) $url = "http://" . $url;
					
					if($url=='' || !validUrl($url, 0)) { echo "<h3>No URL</h3>";}
					else
					{
						/*	Collection of URL's found on parsed web pages
						*	Index Array
						*/
						$found_urls=array();
						
						/*	Collection of unique URL's found throught website and crawled
						*	Associative Array
						*/
						$crawled_urls=array();
				
						$urlParts = parse_url($url);
						
						echo "<h2>Result - URL's Found</h2><ul>";

						// Construct root URL
						$found_urls[0] = $urlParts['scheme'] . "://" . $urlParts['host'] . "/";

						/**
						*	Index of last array element
						*	
						*	Used as a better replacement for array_push() to add a new element to $found_urls
						*/
						$fnum = 0;

						// Parse root URL
						crawl_site($found_urls[0], $crawled_urls, $found_urls, $fnum);

						
						$tog = true;
						$count = 0;
						
						// Parse URL's added to $found_urls
						// Continue loop until all URL's in array have been analyzed
						while ($tog)
						{
							if (isset($found_urls[++$count]))
							{						
								crawl_site($found_urls[$count], $crawled_urls, $found_urls, $fnum);
							}
							else { $tog = false; }
						}
						foreach($crawled_urls as $i => $j) { echo $i . "<br />"; } 
						
						/** 
						*	Create external XML file with SiteMap elements using DOMDocument
						*/
						
						// Open or create XML file
						$xmlMap = fopen("./sitemap.xml", "w+");
						
						$xml = new DOMDocument('1.0');

						// Format Tags
						$xml->formatOutput = true;
						
						// urlset tag (root)
						$urlSetX = $xml->createElement("urlset");
						$xml->appendChild($urlSetX);
						
						// Loop through all valid URL's
						foreach($crawled_urls as $i => $j)
						{
							// url tag
							$urlX = $xml->createElement("url");
							$urlSetX->appendChild($urlX);
							
							// loc tg
							$locX = $xml->createElement("loc");
							$urlX->appendChild($locX);	

							// add URL as text for loc node
							$text = $xml->createTextNode($i);
							$locX->appendChild($text);
						}
						
						fwrite($xmlMap, $xml->saveXML());
						fclose($xmlMap);
						echo '<p>View generated <a href="./sitemap.xml" target="_blank" >sitemap.xml</a> file</p>';
					}
				}
			?>
		</div>
		<style>
			input
			{
				border:none;
				padding:8px;
			}
			
			ul
			{
				line-height: 25px;
			}
			
			#content
			{
				style="margin-top:10px;
				height:100%;"
			}
		</style>
	</body>
</html>
