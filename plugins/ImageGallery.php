<?
/**
 * The warts:
 * 	-Image attributes need input types, so they need to go into the super admin.
 * 	-Need to re-think the whole multiple thumbs thing.  Great idea so that we can have different views of the
 * 		same images, but crappy implementation.  Should be able to redefine everything about the look.  Try this...
 * 		Break it into two plugins.  Image gallery (the images without a display) and Image gallery view.
 *	-Need hack for 1 wide to support prev/next cells (colspan=2)
 * 	-Need testing for 1 wide and 1 high, etc.
 *  -Include plugin# in page variable name so more than one gallery can appear on the same page.
 * 
 * What if we allow plug-ins to be used inside the CMS instead of just the web site?  Then we design forms for 
 * repeater data.  Forms could contain attachments, which could be images for the gallery.  Too complicated for 
 * the developers?  Maybe.
 */
class ImageGallery extends PluginBase 
{
	function GetTitle()
	{
		return 'Image Gallery Plugin';
	}

	function Upgrade()
	{
	}

	function GetDefaultConfig()
	{
		$r = array();
		
		$this->BuildConfig(
			$r,
			'Cells Across', 
			3, 
			'input.3.3', 
			0);
		$this->BuildConfig(
			$r, 
			"Cells Down", 
			3, 
			"input.3.3", 
			0);
		$this->BuildConfig(
			$r, 
			"Aspect Ratios", 
			"Thumb Nail:200x150m", 
			"input.60.4096", 
			0);
		$this->BuildConfig(
			$r, 
			"Click for Full Size", 
			"Yes", 
			'radio.'.serialize(array('Yes'=>'Yes','No'=>'No')),
			0);
		$this->BuildConfig(
			$r, 
			"Next Box Location (x,y)", 
			"", 
			"input.6.6", 
			0);
		$this->BuildConfig(
			$r, 
			"Previous Box Location (x,y)", 
			"", 
			"input.6.6", 
			0);
		$this->BuildConfig(
			$r, 
			"Next Box Contents", 
			"<a href=\"{0}\">Next</a>", 
			"input.50.4096", 
			0);
		$this->BuildConfig(
			$r, 
			"Previous Box Contents", 
			"<a href=\"{0}\">Next</a>", 
			"input.50.4096", 
			0);
		$this->BuildConfig(
			$r, 
			"Thumb Box Contents", 
			"<h2>{ATTR:TITLE}</h2><img class=\"ThumbImage\" alt=\"{ATTR:ALT TEXT}\" src=\"{IMAGE:THUMB NAIL}\" /><br>{ATTR:CAPTION}", 
			"html.600.400", 
			0);
		$this->BuildConfig(
			$r, 
			"Full Box Contents", 
			"<h2>{ATTR:TITLE}</h2><img class=\"FullImage\" alt=\"{ATTR:ALT TEXT}\" src=\"{IMAGE:FULL SIZE}\" /><br>{ATTR:CAPTION}", 
			"html.600.400", 
			0);
		$this->BuildConfig(
			$r, 
			"Attributes", 
			"Title,Caption,Alt Text", 
			"input.60.4096", 
			0);
		
		return $r;
	}
	
	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE `galleryattribute` (
		  `id` int(11) NOT NULL auto_increment,
		  `instance` int(11) NOT NULL default '0',
		  `key` varchar(60) NOT NULL default '',
		  `value` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `instance_2` (`instance`,`key`),
		  KEY `instance` (`instance`)
		)");
		Zymurgy::$db->query("CREATE TABLE `galleryimage` (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`instance` INT NOT NULL ,
			`disporder` INT NOT NULL ,
			`image` VARCHAR(60)
			PRIMARY KEY ( `id` )
			)");
	}
	
	function GetUninstallSQL()
	{
		return 'drop table galleryattribute;drop table galleryimage';
	}
	
	function ThumbFolder()
	{
		global $ZymurgyRoot;
		$thumbs = $ZymurgyRoot.'/UserFiles/Gallery';
		@mkdir($thumbs);
		$thumbs .= '/'.str_replace(
			array('/','.','$','\\',' '),
			array('', '', '', '',  '_'),$this->InstanceName);
		@mkdir($thumbs);
		return $thumbs;
	}
	
	function MakeThumb($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		//Now run ImageMagick.  Need to figure out options for thumbs that extract only a portion of the original.
		//Resize first and then crop resized image:
		//convert -crop 100x100+50+60 030101024026.JPG[400x200] out2.jpg
		
		//Determine ratio of change
		$wrat = $sw/$dw;
		$hrat = $sh/$dh;
		//Determine x,y offset on resized image
		$sxs = floor($sx*$wrat);
		$sys = floor($sy*$hrat);
		//Determine resized full width and height
		$swidth = floor($width * $wrat);
		$sheight =floor($height* $hrat);
		
		$out = system("convert -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile\[{$swidth}x{$sheight}] $destfile",$r);
		return $r;
	}
	
	function MakeFixedThumb($w,$h,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($this->srcfile);
		$tratio = ($w/$h);
		$sratio = ($width/$height);
		if ($sratio > $tratio)
		{
			//Clip sides.  How much?
			$ratio = ($height/$h); 
			$sw = floor($w * $ratio);
			$sh = $height;
			$sx = floor(($width-$sw)/2);
			$sy = 0;
		}
		if ($sratio < $tratio)
		{
			//Clip top and bottom
			$ratio = ($width/$w);
			$sw = $width;
			$sh = floor($h * $ratio);
			$sx = 0;
			$sy = floor(($height-$h)/2);
		}
		if ($sratio == $tratio)
		{
			return $this->MakeQuickThumb($w,$h,$srcfile,$destfile);
		}
		return MakeThumb($sx,$sy,$sw,$sh,$w,$h,$srcfile,$destfile);
	}
	
	function MakeQuickThumb($maxw,$maxh,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		$w = $width;
		$h = $height;
		if ($maxw < $w)
		{
			//Scale to fit within the max width
			$ratio = $maxw / $w;
			$w = $maxw;
			$h = floor($h * $ratio);
		}
		if ($maxh < $h)
		{
			//Scale to fit within max height
			$ratio = $maxh /$h;
			$h = $maxh;
			$w = floor($w * $ratio);
		}
		//Now run ImageMagick
		$cmd = "convert -geometry $w x $h $srcfile $destfile";
		echo "$cmd<br>";
		$out = system($cmd,$r);
		//echo "[$out,$r]<br>";
		return $r;
	}

	function Render()
	{
		global $ZymurgyRoot;
		
		//Load pages
		$pages = array();
		$sql = "select *,galleryimage.id as iid from galleryimage join galleryattribute on galleryimage.id=galleryattribute.image order by disporder";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri) die("Unable to load gallery.<!-- sql:$sql, error:".Zymurgy::$db->error()." -->");
		$maxx = $this->config['Cells Across'];
		$maxy = $this->config['Cells Down'];
		$firstpagecount = $pagecount = $maxx * $maxy;
		$nextx = $nexty = $prevx = $prevy = 0;
		if ($this->config['Next Box Location (x,y)'] != '')
		{
			$firstpagecount--;
			$pagecount--;
			list($nextx,$nexty) = explode(',',$this->config['Next Box Location (x,y)']);
		}
		if ($this->config['Previous Box Location (x,y)']!='')
		{
			$pagecount--;
			list($prevx,$prevy) = explode(',',$this->config['Previous Box Location (x,y)']);
		}
		$lastimage = 0;
		$page = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			if ($row['iid']!=$lastimage)
			{
				//New image.  Need a new page?
				if (count($pages)==0)
					$maxcount = $firstpagecount;
				else 
					$maxcount = $pagecount;
				if (count($page) == $maxcount)
				{
					//Yes, start a new page
					$pages[] = $page;
					$page = array();
				}
				$page[$row['iid']] = array(); //Keys are image numbers, values are arrays of attributes
				$lastimage = $row['iid'];
			}
			$page[$row['iid']][strtoupper($row['key'])] = $row['value'];
		}
		//Need to add last page?
		if (count($page)>0) $pages[] = $page;
		
		//Ok, we have pages now render the current page.
		if (array_key_exists('page',$_GET))
			$pagenum = 0 + $_GET['page'];
		else 
			$pagenum = 0;
		$page = $pages[$pagenum];
		$x = $y = 1;
		$GalleryFolder = substr($this->ThumbFolder(),strlen($ZymurgyRoot));
		//Find path to the datagrid and use it to build prev/next links
		$fp = explode('/',__FILE__);
		array_pop($fp); //Remove my own file name
		array_pop($fp); //Remove plugins folder;
		require_once(implode('/',$fp).'/datagrid.php');
		$nextlink = DataGrid::BuildSelfReference(array('page'=>$pagenum+1));
		$prevlink = DataGrid::BuildSelfReference(array('page'=>$pagenum-1));
		$tds = $next = $prev = '';
		if ($pagenum>0)
			$prev = str_replace('{0}',$prevlink,$this->config['Previous Box Contents']);
		if ($pagenum<(count($pages)-1))
			$next = str_replace('{0}',$nextlink,$this->config['Next Box Contents']);
		echo "<table class=\"ImageGallery\">\r\n";
		echo "<tr>";
		foreach($page as $image=>$attrs)
		{
			if (($x==$nextx) && ($y==$nexty) && ($pagenum<(count($pages)-1)))
			{
				echo "<td>$next</td>";
			}
			else if (($x==$prevx) && ($y==$prevy) && ($pagenum>0))
			{
				echo "<td>$prev</td>";
			}
			else 
			{
				$search = array("{IMAGE:THUMB NAIL}");
				$replace = array("$GalleryFolder/$image/Thumb_Nail.jpg");
				foreach($attrs as $key=>$value)
				{
					$search[] = "{ATTR:$key}";
					$replace[] = $value;
				}
				$cell = str_replace($search,$replace,$this->config['Thumb Box Contents']);
				echo "<td>$cell</td>";
			}
			$x++;
			if (($x > $maxx) && ($y < $maxy))
			{
				$x = 1;
				echo "</tr>\r\n<tr>";
			}
		}
		if (($y<$maxy))
			echo "</tr>\r\n";
		if ($nextx==0)
		{
			//Draw new row for prev/next
			for ($n=2; $n<$maxx; $n++)
				$tds .= '<td></td>';
			echo "<tr class=\"Navigation\"><td align=\"left\">$prev".
				"</td>$tds<td align=\"right\">$next</td></tr>\r\n";
		}
		echo "</table>";
		return;
	}
	
	function AdminMenuText()
	{
		return 'Image Gallery';
	}
	
	function RenderImageAdmin()
	{
		//Allow admin to upload, delete, change order and attributes for images.
		global $ImageGalleryAdmin,$ZymurgyRoot;
		
		$ImageGalleryAdmin = $this;
		$ds = new DataSet('galleryimage','id');
		$ds->AddColumns('id','instance','disporder','image');
		$ds->AddDataFilter('instance',$this->iid);
		$ds->OnInsert = $ds->OnUpdate = "ImageGalleryUpdate";
		$dg = new DataGrid($ds);
		$dg->AddConstant('instance',$this->iid);
		$GalleryFolder = substr($this->ThumbFolder(),strlen($ZymurgyRoot));
		$dg->AddColumn('Image','id',"<img src=\"$GalleryFolder/{0}/CPThumb.jpg\">");
		$dg->AddColumn('','id',"<a href=\"pluginadmin.php?pid={$_GET['pid']}&iid={$_GET['iid']}&name=".
			urlencode($_GET['name'])."&image={0}\">Attributes</a>");
		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->AddAttachmentEditor('image','Image:');
		$dg->insertlabel="Upload New Image";
		$dg->Render();
	}
	
	function RenderAttributeAdmin()
	{
		$attrs = explode(',',$this->config['Attributes']);
		$image = 0 + $_GET['image'];
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			foreach ($attrs as $attr)
			{
				//Try an update first.  If zero records then try an insert.
				$attr = str_replace(' ','_',$attr);
				$sql = "insert into galleryattribute (image,`key`,value) values ($image,'".
					Zymurgy::$db->escape_string($attr)."', '".Zymurgy::$db->escape_string($_POST[$attr])."')";
				$ri = Zymurgy::$db->query($sql);
				if (!$ri)
				{
					if (Zymurgy::$db->errno()==1062)
					{
						$sql = "update galleryattribute set value='".Zymurgy::$db->escape_string($_POST[$attr])."' where (`key`='".
							Zymurgy::$db->escape_string($attr)."') and (image=$image)";
						$ri = Zymurgy::$db->query($sql);
						if (!$ri) die("Couldn't create attribute ($sql): ".Zymurgy::$db->error());
					}
					else 
						die("Couldn't set attribute ($sql): ".Zymurgy::$db->error());
				}
			}
			header("Location: pluginadmin.php?pid={$_GET['pid']}&iid={$_GET['iid']}&name=".urlencode($_GET['name']));
		}
		else 
		{
			//Load attributes
			$sql = "select `key`,value from galleryattribute where image=$image";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri) die("Couldn't load attributes ($sql): ".Zymurgy::$db->error());
			$attrvalues = array();
			while (($row = Zymurgy::$db->fetch_array($ri))!== false)
			{
				$attr = str_replace(' ','_',$row['key']);
				$attrvalues[$attr] = $row['value'];
			}
			//Show form
			echo "<form method=\"post\"><table>\r\n";
			foreach ($attrs as $attr)
			{
				$attrkey = str_replace(' ','_',$attr);
				if (array_key_exists($attrkey,$attrvalues))
					$value = $attrvalues[$attrkey];
				else 
					$value = '';
				echo "<tr><th align=\"right\">$attr:</th><td><input type=\"text\" name=\"$attr\" size=\"50\" maxlength=\"4096\" value=\"".htmlentities($value)."\" /></td></tr>\r\n";
			}
			echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\" /></td></tr>\r\n";
			echo "</table></form>";
		}
	}
	
	function RenderAdmin()
	{
		if (array_key_exists('image',$_GET))
			$this->RenderAttributeAdmin();
		else 
			$this->RenderImageAdmin();
	}

	function RenderSuperAdmin()
	{
		$ds = new DataSet('galleryattributetype','id');
		$ds->AddColumns('id','name','specifier');
		$dg = new DataGrid($ds);
		$dg->AddColumn('Name','name');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->AddInput('name','Name:',60,40);
		$dg->AddInput('specifier','Input Specifier:',1024,60);
		$dg->insertlabel = 'Add new Attribute';
		$dg->Render();
		if (((array_key_exists('action',$_GET)) && ($_GET['action']=='insert')) || (array_key_exists('editkey',$_GET)))
		{
			echo "<table><tr><th>Input Specifier Quick Reference:</th><td>input.size.maxlength<br>
				textarea.width.height<br>
				html.widthpx.heightpx<br>
				checkbox (.checked if defaults to checked)<br>
				radio.extra (or allow user to supply extra, or use serialize to embed comas)<br>
				drop.extra (or allow user to supply extra, or use serialize to embed comas)<br>
				attachment<br>
				money<br>
				unixdate<br>
				lookup.table<br>
				verbiage.display text (output only)</td></tr></table>";
		}
	}
}

function ImageGalleryUpdate($values)
{
	//Can I move this into the class with :: to avoid collisions?
	global $ImageGalleryAdmin;
	$dest = $ImageGalleryAdmin->ThumbFolder().'/'.$values['galleryimage.id'];
	@mkdir($dest);
	
	//Get source data
	$safefn=$_FILES['galleryimage_image']['tmp_name'];
	if (file_exists('/usr/bin/jhead'))
		exec("/usr/bin/jhead -purejpg $safefn");
	
	//Build required thumbs
	$ImageGalleryAdmin->MakeQuickThumb(100,100,$safefn,"$dest/CPThumb.jpg");
	$arp = explode(',',$ImageGalleryAdmin->config['Aspect Ratios']);
	foreach($arp as $ar)
	{
		list($arname,$arvalue) = explode(':',$ar,2);
		$arname = str_replace(
			array('/','.','$','\\',' '),
			array('', '', '', '',  '_'),$arname);
		if (is_numeric(substr($arvalue,-1)))
			$style = 'm';
		else 
		{
			$style = substr($arvalue,-1);
			$arvalue = substr($arvalue,0,-1);
		}
		list($width,$height) = explode('x',$arvalue,2);
		$destfile = "$dest/$arname.jpg";
		if ($style=='m')
			$ImageGalleryAdmin->MakeQuickThumb($width,$height,$safefn,$destfile);
		else 
			$ImageGalleryAdmin->MakeFixedThumb($width,$height,$safefn,$destfile);
	}
	//echo "Done update method."; exit;
}

function ImageGalleryFactory()
{
	return new ImageGallery();
}
?>