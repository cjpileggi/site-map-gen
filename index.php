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
				// potential bug: detects the same page twice when the trailing slash makes a difference
				//error_reporting(E_ALL);
				//ini_set('display_errors', '1');
				//include("./simple_html_dom.php");
				$crawled_urls=array();
				$found_urls=array();
				$cnt = 0;
					
				function rel2abs2($rel, $base)
				{
					/* return if already absolute URL */
					if (parse_url($rel, PHP_URL_SCHEME) != '') return ($rel);

					/* queries and anchors */
					if ($rel[0] == '#' || $rel[0] == '?') return ($base . $rel);

					/*me*/
					if (substr($rel,0,1) != '/' && substr($rel,0,2) != '//' && substr($rel,0,2) != './' && substr($rel,0,3) != '../') $rel = "./" . $rel;

					/* parse base URL and convert to local variables: $scheme, $host, $path, $query, $port, $user, $pass */
					extract(parse_url($base));

					if (!isset($path)) $path = '';
					
					/* remove non-directory element from path */
					$path = preg_replace('#/[^/]*$#', '', $path);

					/* destroy path if relative url points to root */
					if ($rel[0] == '/') $path = '';

					/* dirty absolute URL */
					$abs = '';

					/* do we have a user in our URL? */
					if (isset($user))
					{
						$abs .= $user;

						/* password too? */
						if (isset($pass)) $abs .= ':' . $pass;

						$abs .= '@';
					}
					$abs .= $host;

					/* did somebody sneak in a port? */
					if (isset($port)) $abs .= ':' . $port;

					$abs .= $path . '/' . $rel . (isset($query) ? '?' . $query : '');

					/* replace '//' or '/./' or '/foo/../' with '/' */
					$re = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
					for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {}

					/* absolute URL is ready! */
					return ($scheme . '://' . $abs);
				}


				function perfect_url($u,$b)
				{
					// Original URL in parts
					$bp=parse_url($b);

					// Add scheme and host if URL is not just "/" 
					if(isset($bp['path']) && $bp['path']!="/")
					{
						if($bp['scheme']==""){$scheme="http";}else{$scheme=$bp['scheme'];}
						$b=$scheme."://".$bp['host']."/";
					}
					if(substr($u,0,2)=="//") { $u="http:".$u; }

					if(substr($u,0,4)!="http") { $u=rel2abs2($u,$b); }

					return $u;
				}
				
				function realUrl($url)
				{
					$file_headers = @get_headers($url);

					return !(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found' || $file_headers[0] == 'HTTP/1.1 410 Gone');
				}
				
				function crawl_site($url)
				{
					// Obtain global $crawled_urls array
					global $rootUrl;
					global $found_urls;
					global $crawled_urls;
					global $fnum;

					if(!array_key_exists($url, $crawled_urls))
					{
						$crawled_urls[$url] = 1;
						
						//Create a new DOM document
						$dom = new DOMDocument;
						
						//Parse the HTML. The @ is used to suppress any parsing errors
						//that will be thrown if the $html string isn't valid XHTML.
						@$dom->loadHTML(file_get_contents($url));

						//Get all links. You could also use any other tag name here,
						//like 'img' or 'table', to extract other tags.
						$links = $dom->getElementsByTagName('a');
						
						//Iterate over the extracted links and display their URLs
						foreach ($links as $link){

							$furl = $rootUrl . ltrim(parse_url(rel2abs2($link->getAttribute('href'), $url),PHP_URL_PATH) , '/');
							
							if(!array_key_exists($furl, $crawled_urls) && realUrl($furl))
							$found_urls[++$fnum] = $furl;
						}
					}
				}
					

				if(isset($_POST['submit']))
				{
					$url=$_POST['url'];
					
					if($url=='' || !realUrl($url)) { echo "<h3>No URL</h3>";}
					else
					{
						//echo "$scheme  s   $host  h  $port p $user u $pass pass $path path  $query  q  $fragment f";
						// scheme present .com is host;  else .com is path 
						if (parse_url($url, PHP_URL_SCHEME) == null) 
						{ 
							$url = "http://" . $url;
						}
						
						$urlParts = parse_url($url);
						
						$rootUrl = $urlParts['scheme'] . "://" . $urlParts['host'] . "/";

						echo "<h2>Result - URL's Found</h2><ul>";
						//array_push($crawled_urls, $nUrl);
						$found_urls[0] = $rootUrl;
						$fnum = 0;
						$cnum = 0;
						
						crawl_site($found_urls[0]);
						//crawl_site($found_urls["/"]);
						/*crawl_site($found_urls["/"]);*/

						$tog = true;
						$count = 0;
						while ($tog)
						{
							if (isset($found_urls[++$count]))
							{
								crawl_site($found_urls[$count]);
							}
							else { $tog = false; }
						}
						foreach($crawled_urls as $i => $j)
						{
							echo $i . "<br />";
						}

						
						//$xmlMap = fopen("sitemap.xml", "w+");
						//fclose($xmlMap);
						
						//Create XML document
						/*$xml = new DOMDocument();
						$xml_url = $xml->createElement("urlset");
						
						foreach($crawled_urls as $i => $j)
						{
							$xml_url = $xml->createElement("url");
							$xml_loc = $xml->createElement("loc");
							$xml_loc->appendChild($xml->createTextNode($i));
							$xml_url->appendChild( $xml_loc );
							$xml->appendChild( $xml_album );
						}
						

						

						$xml->save("./sitemap.xml");
						//caching? recursion, like breadthfirst , isset() */
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
