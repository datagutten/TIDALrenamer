<?Php
require 'config.php';
require_once 'TIDALtools/tidalinfo.class.php';
$info=new tidalinfo;
require_once 'audio-metadata/metadata.php';
$metadata=new metadata;
require_once 'TIDALrenamer.class.php';
$renamer=new TIDALrenamer;

if(!isset($options))
	$options = getopt("",array('compilation::','album:','playlist:','order','id','nodelete','flac'));
if(empty($options))
	$error="Sample usage:\nRename a playlist:\tphp rename.php --order --playlist 43acd778-4985-4304-a460-37e5565881b8\nRename an album:\tphp rename.php --order --album 530705\nAlbum and playlist parameters also accepts URLs containing the id.\n";
else
{
	if(isset($options['order']))
		$path=$config['inpath_order'];
	else
		$path=$config['inpath_id'];

	if(isset($options['id']) && !empty($options['id']))
		$files=array_merge(glob($path.'/'.$options['id'].'.mp4'), glob($path.'/'.$options['id'].'.m4a'),glob($path.'/'.$options['id'].'.flac'));
	else
		$files=array_merge(glob($path.'/*.mp4'), glob($path.'/*.m4a'),glob($path.'/*.flac'));
	if(empty($files))
		die(sprintf("No files to be renamed in %s\n",$path));

	sort($files);
	$filecount=count($files);
	if(isset($options['album']))
	{
		if(preg_match('^.+album/([0-9]+)^',$options['album'],$albumid)) //Get album ID from URL
			$options['album']=$albumid[1];
		if(!is_numeric($options['album']))
			$error="Invalid album id or URL: {$options['album']}";
		else
		{
			$albuminfo=$info->album($options['album']); //Get info about the album itself
			$tracklist=$info->album($options['album'],true);
			if($filecount!=$albuminfo['numberOfTracks'])
				$error="Album contains {$albuminfo['numberOfTracks']} tracks, but there is $filecount files in $path";
		}
	}
	elseif(isset($options['playlist']))
	{
		if(preg_match('/[a-f0-9\-]{36}/',$options['playlist'],$tracklist_id))
		{
			$options['playlist']=$tracklist_id[0];
			$tracklist=$info->playlist($options['playlist'],'tracks'); //Get the tracks on the playlist
			if($filecount!=$tracklist['numberOfTracks'])
				$error="Playlist countains {$tracklist['numberOfTracks']} tracks, but there is $filecount files in $path";
		}
		else
			$error="Invalid playlist id or URL: {$options['playlist']}";
	}
	elseif(isset($options['order']))
		$error="--album or --playlist is required when using --order\n";
	
}

if(!empty($error))
	echo $error."\n";
else
{
	foreach($files as $key=>$file)
	{
		$trackcounter=$key+1;
		$pathinfo=pathinfo($file);

		if(isset($options['order'])) //Files named by album or playlist position
			$trackinfo=$tracklist['items'][$key];
		elseif(isset($options['id'])) //Files names by track id
		{
			$trackinfo=$info->track($pathinfo['filename']);
			$albuminfo=$info->album($trackinfo['album']['id']);
		}
		else
			throw new Exception("No valid options");

		if(isset($options['flac']) && $pathinfo['extension']!='flac') //Convert to flac
		{
			$tempfile=$metadata->convert_to_flac($file);
			if($tempfile===false)
				echo $metadata->error."\n";
			else
			{
				unlink($file); //Remove original file
				$file=$tempfile;
			}
		}
		if(isset($options['playlist']))
			$trackinfo=$renamer->prepare_metadata($trackinfo,$albuminfo,true);
		else
			$trackinfo=$renamer->prepare_metadata($trackinfo,$albuminfo,false);
		if($trackinfo===false)
		{
			echo $info->error."\n";
			continue;
		}
		$return=$metadata->metadata($file,$config['sortedpath'],$trackinfo);
		if($return===false)
		{
			if(empty($metadata->error))
				echo "Metadata returned false with no error message\n";
			else
				echo $metadata->error."\n";	
		}
		else
		{
			echo "Renamed and tagged $file\n";
			if(!isset($options['nodelete']))
				unlink($file);
		}
	}
}