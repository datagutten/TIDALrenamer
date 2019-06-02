<?Php
$config = require 'config.php';
require 'vendor/autoload.php';
$info=new tidalinfo;
$metadata=new AudioMetadata;
$renamer=new TIDALrenamer;

if(!isset($options))
	$options = getopt("",array('compilation::','album:','playlist:','order','id::','nodelete','flac'));
if(empty($options)) {
    die(
    "Sample usage:\n
	Rename a playlist:\t
	php rename.php --order --playlist 43acd778-4985-4304-a460-37e5565881b8\n
	Rename an album:\tphp rename.php --order --album 530705\n
	Album and playlist parameters also accepts URLs containing the id.\n");
}

try
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

			$albuminfo=$info->album($options['album']); //Get info about the album itself
			$tracklist=$info->album($options['album'],true);
			if($filecount!=$albuminfo['numberOfTracks'])
				die("Album contains {$albuminfo['numberOfTracks']} tracks, but there is $filecount files in $path");
	}
	elseif(isset($options['playlist']))
	{
		if(preg_match('/[a-f0-9\-]{36}/',$options['playlist'],$playlist_id))
		{
			$tracklist=$info->playlist($playlist_id[0]); //Get the tracks on the playlist
			if($filecount!=$tracklist['numberOfTracks'])
				die("Playlist countains {$tracklist['numberOfTracks']} tracks, but there is $filecount files in $path");
		}
		else
			die("Invalid playlist id or URL: {$options['playlist']}");
	}
	elseif(isset($options['order']))
		die("--album or --playlist is required when using --order\n");

}
catch (Exception $e) {
    die($e->getMessage() . "\n");
}

foreach($files as $key=>$file)
{
    $trackcounter=$key+1;
    $pathinfo=pathinfo($file);

    if(isset($options['order'])) //Files named by album or playlist position
        $trackinfo=$tracklist['items'][$key];
    elseif(isset($options['id'])) //Files names by track id
    {
        try {
            $trackinfo=$info->track($pathinfo['filename']);
            $albuminfo=$info->album($trackinfo['album']['id']);
        }
        catch (Exception $e) {
            echo $e->getMessage()."\n";
            continue;
        }

    }
    else
        throw new InvalidArgumentException("No valid options");

    try
    {
        if(isset($options['flac']) && $pathinfo['extension']!='flac') //Convert to flac
        {
            $tempfile=$metadata->convert_to_flac($file);
            unlink($file); //Remove original file
            $file=$tempfile;
        }

        if(isset($options['playlist']))
            $trackinfo=$renamer->prepare_metadata($trackinfo,$albuminfo,true);
        else
            $trackinfo=$renamer->prepare_metadata($trackinfo,$albuminfo,false);

        $metadata->metadata($file,$config['sortedpath'],$trackinfo);
        echo "Renamed and tagged $file\n";
        if(!isset($options['nodelete']))
            unlink($file);
    }
    catch (Exception $e)
    {
        echo $e->getMessage()."\n";
        continue;
    }
}