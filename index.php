<!DOCTYPE html>
<html>
	<head>
		<title>Site Map Generator</title>
	</head>
	<body>
		<div id="content">
			<h1>Site Map Generator</h1>
				<form action="index.php" method="POST">
					URL: <input name="url" placeholder="http://www.chrispileggi.com"/>
					<input type="submit" name="submit" value="Start Crawling"/>
				</form>
				<?php
					error_reporting(E_ALL);
					ini_set('display_errors', '1');
					include("simple_html_dom.php");
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
					

					/*function rel2abs($rel, $base)
					{
						// URL is already absolute if there is no scheme
						if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel; // calls url scheme PHP_URL_SCHEME ex. string(4) "http"
						
						// The first character of $rel is not the start of GET variable(s) or an anchor
						if ($rel[0]=='#' || $rel[0]=='?') return $base . $rel;
						
						// Extract parsed URL as seperate variables  (ALWAYS THE SAME??)
						extract(parse_url($base));

						// Remove the last "/" and anything beyond it in the $path variable
						$path = preg_replace('#/[^/]*$#', '', $path);

						// $path is null if the beginning of $rel is a /
						if ($rel[0] == '/') { $path = ''; }

						// Construct the absolute URL ($host is example.com)
						$abs = "$host$path/$rel";

						// /./ or // and /no 2 dots any characters but / dot dot / (ex /jnjnjkn/../)
						$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');

						// Shorthand for loop; 
						//for($n=1; $n>0;$abs=preg_replace($re,'/', $abs,-1,$n)){echo $abs . " " . ++$count . " " . $n . "<br />";}
						
						// Replace values in string that match array
						$abs=preg_replace($re,'/', $abs);

						$abs=str_replace("../","",$abs);

						// ex: http
						return $scheme.'://'.$abs;
					} */

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
					
					function del($url)
					{
						$file_headers = @get_headers($url);

						return !$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found' || $file_headers[0] == 'HTTP/1.1 410 Gone' ? "na" : "ya";
					}
					
					function crawl_site($url)
					{
						// Obtain global $crawled_urls array
						global $rootUrl;
						global $found_urls;
						global $crawled_urls;
						global $cnum;
						global $fnum;


						if(!array_key_exists($url, $crawled_urls) && del($url) == "ya")
						{
							$crawled_urls[$url] = 1;
							
							$html = file_get_html($url);
							
							foreach($html->find("a") as $li)
							{
								//if (parse_url($li->href, PHP_URL_SCHEME) . "://" . parse_url($li->href, PHP_URL_HOST) == "http://chrispileggi.com") echo perfect_url($li->href, "http://chrispileggi.com") . "<br />";
								
								//$upts = parse_url($li->href);
								
																
								$furl = $rootUrl . ltrim(parse_url(rel2abs2($li->href, $url),PHP_URL_PATH) , '/');
								
								if(!array_key_exists($furl, $crawled_urls) && del($furl) == "ya")
								$found_urls[++$fnum] = $rootUrl . ltrim(parse_url(rel2abs2($li->href, $url),PHP_URL_PATH) , '/');
								
								//$nurl = perfect_url($li->href, $url);
								//echo $nurl . "<br />";
								//$found_urls[++$fnum] = $nurl; 
								
								///$found_urls[++$fnum] = parse_url($nurl, PHP_URL_PATH) == "/" ? $url : $url . ltrim(parse_url($nurl, PHP_URL_PATH), '/');
								
								//echo $found_urls[$fnum] . "<br /><br />";
								//$end = ltrim(parse_url($nurl, PHP_URL_PATH), '/');
								//$found_urls[++$fnum] =  $end == "/" ? $url : $url . parse_url($nurl, PHP_URL_PATH);
								
								//echo $found_urls[$fnum] . "<br />";
								
								
								//echo $nurl . " " . $np['path']  . "<br />";
							}
						}

						//$uen=urlencode($u);
						/*//echo "URL Path: " . $urlParts["path"] . "<br />";
						if (isset($urlParts["path"])) { $current = $urlParts["path"]; }//{ $current = "/"; }
						//else 
						
						$current = $rootUrl . "" . $current;
						
						//echo "Current Path: " . $current . "<br />";
						//$crawled_urls[$current] = 0; 
						// Non alpha-numeric characters replaced
						
						//echo $uen;
						// $uen is not a key in crawled urls array or the date stored for $uen is less that 25 seconds ago
						
						//if((array_key_exists($uen,$crawled_urls)==0 || $crawled_urls[$uen] < date("YmdHis",strtotime('-25 seconds', time()))))
						if (!isset($crawled_urls[$current]))
						{   
							//echo "IN isset Current: " . del($current) ."<br />";
							if($current!='' && substr($current,0,4)!="mail" && substr($current,0,4)!="java" && del($current) == "ya")
							{
								echo "IN isset Current inner <br />";
								// Add url as key in found_urls adn give a 1
								$crawled_urls[$current] = 0;
							}
							//echo "OUT isset Current <br />";
						}				
						if (isset($crawled_urls[$current]) && $crawled_urls[$current] == 0 && del($current) == "ya")
						{
							//echo "IN assign Current <br />";
							$crawled_urls[$current] = 1;
							// Collect html elements as dom from URL
							

							$html = file_get_html($current);

							// Store encoded url and timestamp
							//$crawled_urls[$uen]=date("YmdHis");

							// The URL does not begin with "mail" or "java" and url does not exist in found_urls array

								// All anchor tags in the $html object
								foreach($html->find("a") as $li)
								{
									//echo "IN HREF Loop<br />";
									// Normalize URL and break as array
									//$url=perfect_url($li->href,$u);
									//$enurl=urlencode($url);
									$urlParts2 = parse_url($li->href);
									//echo "URL PATH 2: " . $urlParts2["path"] . " <br />";
									$current2 = ($urlParts2["path"] == "" ? "/" : $urlParts2["path"]);
									//echo "Current2: " . $current2 . " <br />";
									
									if (substr($current2,0,3)=="../") {$current2 = substr($current2, 2); }
									else if (substr($current2,0,2)=="./")  {$current2 = substr($current2, 1); } 
									else if (substr($current2,0,1)!="/")  { $current2 = "/" . $current2; } 
									//echo "New Current2: " . $current2 . " <br />";
									if($current2!='' && substr($current2,0,4)!="mail" && substr($current2,0,4)!="java")
									{
										
										//echo "In found Assign <br />";
										if (substr($current,-1)!="/")  { $current = substr($current, 0, -1); } 
										$current2 = $current . "" . $current2; //substr($nUrl,0,-1)
										//echo "New NEW Current2: " . $current2 . " <br />";
										// Add url as key in found_urls adn give a 1
										array_push($found_urls, preg_replace('/([^:\/])(\/+)/','$1/',$current2));
									
										
									}
									//echo "OUT HREF Loop<br />";
								}
							}
							//echo "OUT assign Current<br />"; */
					}
						

					if(isset($_POST['submit']))
					{
						$url=$_POST['url'];
						
						if($url=='' || del($url) == "na") { echo "<h3>No URL</h3>";}
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
							echo $rootUrl . "<br />";
							while (list($var, $val) = each($urlParts)) {
								print "$var is $val <br />";
							}
							echo "<h2>Result - URL's Found</h2><ul style='word-wrap: break-word;width: 400px;line-height: 25px;'>";
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
									echo "<br />passed: " . $found_urls[$count] . "<br />";
									crawl_site($found_urls[$count]);
								}
								else { $tog = false; }
								echo count($found_urls);
							}


							
							echo "crawl: <br />";
							foreach($crawled_urls as $i => $j)
							{
								echo $i . " " . $j . "<br />";
							}
							
							echo "found: <br />";
							foreach($found_urls as $item)
							{
								echo $item . "<br />";
							} 
							
							/*//while (list($key, $value) = each($crawled_urls)) {
								
								//if ($value!=1) {crawl_site($url . $key);}
							//}
							
							$loop = true;
							while ($loop)
							{
								echo "Begin Loop <br />";
								if ($cnt < count($found_urls) && $cnt<50) 
								{
									echo "Begin Count Loop: ". $cnt ."<". count($found_urls) . "<br />";
									if($crawled_urls[$found_urls[$cnt]] != 1)
									{
										 
										echo "Processed: ". $found_urls[$cnt] . $crawled_urls[$found_urls[$cnt]] . "<br />";
										//echo "b" . count($found_urls) . "e";
										$parts = parse_url($found_urls[$cnt]);
										echo $parts["host"] + $parts["path"];
										crawl_site($found_urls[$cnt]);
									}
									$cnt++;
									echo "End Count Loop: " . $cnt . "<br />";
								}
								else { $loop = false;}
								echo "End Loop <br />";
							}
							
							
							foreach ($crawled_urls as $i => $j)
							{
								echo $i . "<br />";
								//crawl_site($i);
							}
							/*foreach ($found_urls as $i)
							{
								echo $i . "<br />";
								//crawl_site($i);
							}
							
							echo "</ul>";
							
							//$xmlMap = fopen("sitemap.xml", "w+");
							//fclose($xmlMap);
							
							//Create XML document
							$xml = new DOMDocument();
							$xml_album = $xml->createElement("urlset");
							$xml_track = $xml->createElement("url");
							$xml_album->appendChild( $xml_track );
							$xml->appendChild( $xml_album );

							$xml->save("sitemap.xml");
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
				
				#content
				{
					style="margin-top:10px;
					height:100%;"
				}
			</style>
		</body>
</html>
