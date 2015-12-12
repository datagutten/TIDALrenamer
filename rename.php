<?Php
require 'config.php';
require 'tidalinfo_class.php';
$info=new tidalinfo;

require 'audio-metadata/metadata.php';
$metadata=new metadata;
if(!isset($options))
	$options = getopt("",array('compilation::','album:','playlist:','order','id','nodelete'));
if(empty($options))
	$error="Sample usage:\nRename a playlist:\tphp rename.php --order --playlist 43acd778-4985-4304-a460-37e5565881b8\nRename an album:\tphp rename.php --order --album 530705\nAlbum and playlist parameters also accepts URLs containing the id.\n";
else
{
	if(isset($options['order']))
		$path=$inpath_order;
	else
		$path=$inpath_id;
	$files=array_merge(glob($path.'/*.m4a'),glob($path.'/*.flac'));
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
				$error="Album countains {$albuminfo['numberOfTracks']} tracks, but there is $filecount files in $path";
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
			$trackinfo=$info->track($trackid);
			$albuminfo=$info->album($trackinfo['album']['id']);
		}
		else
			throw new Exception("No valid options");

		$trackinfo['track']=$trackinfo['trackNumber'];
		$trackinfo['artist']=$trackinfo['artist']['name'];
		if(!isset($options['playlist']))
		{
			$trackinfo['album']=$trackinfo['album']['title'];
			$trackinfo['albumartist']=$albuminfo['artist']['name'];
			$trackinfo['tracknumber']=$trackinfo['trackNumber'];
			$trackinfo['volumenumber']=$trackinfo['volumeNumber'];
			$trackinfo['totaltracks']=$albuminfo['numberOfTracks'];
			$trackinfo['totalvolumes']=$albuminfo['numberOfVolumes'];
			$trackinfo['cover']=$albuminfo['cover'];
			if($albuminfo['artist']['id']==2935) //If album artist is "Various Artists" the album is a compilation
				$trackinfo['compilation']=true;
		}
		else
		{
			$trackinfo['album']=$tracklist['title'];
			$trackinfo['track']=$trackcounter;
			$trackinfo['totaltracks']=$tracklist['numberOfTracks'];
			$trackinfo['cover']=$tracklist['image'];
			$trackinfo['compilation']=true; //Playlists are always compilations
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