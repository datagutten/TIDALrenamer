<?Php
require 'vendor/autoload.php';
$rename=new TIDALrenamer;
$metadata=new AudioMetadata;
$track_list = '';

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
    $rename->token = $rename->get_token();
    if (isset($options['album'])) {
        $track_list = $rename->album($options['album'], true); //Get track list
        $album = $rename->album($options['album']); //Get album info
        $options['order'] = true;
        $mode = 'order';
        $files = $rename->load_ordered_files();
    } elseif (isset($options['playlist'])) {
        $track_list = $rename->playlist($options['playlist']);
        $mode = 'order';
        $files = $rename->load_ordered_files();
    } elseif (isset($options['id'])) {
        $mode = 'id';
        $files = $rename->load_id_files();
    }
    else
        throw new InvalidArgumentException("No valid options");


    if($mode==='order') {
        if (count($files) != $track_list['totalNumberOfItems'])
            die(sprintf("%d files in input folder, but %d tracks in list\n",
                count($files),
                $track_list['totalNumberOfItems']));
    }
}
catch (TidalError $e)
{
    printf("Error loading information from TIDAL: %s\n", $e->getMessage());
    die();
}

if(empty($files))
    die(sprintf("No files to be renamed in %s\n",$rename->input_path_id));

foreach ($files as $key=>$file)
{
    $pathinfo=pathinfo($file);
    if(isset($options['flac']))
    {
        try {
            printf("Converting %s to flac\n", $file);
            $flac_file = AudioConvert::convert_to_flac($file);
            if(!isset($options['nodelete']))
                unlink($file);
            $file = $flac_file;
        }
        catch (Exception $e)
        {
            echo $e->getMessage()."\n";
            continue;
        }
    }

    try {
        if ($mode == 'id') {
            $track_id = $pathinfo['filename'];
            $renamed_file = $rename->rename($file, $pathinfo['filename']);
        } elseif ($mode == 'order') {
            $trackinfo = $track_list['items'][$key];
            $info = TIDALrenamer::prepare_metadata($trackinfo, $album);
            $renamed_file = $rename->rename($file, $info);
            printf("File %s Title %s Id %d\n", $file, $info['title'], $info['id']);
        }
        else
            throw new InvalidArgumentException('Invalid mode');

        if(!isset($options['nodelete']))
            unlink($file);
    }
    catch (TidalError|Exception $e)
    {
        printf("Error renaming %s: %s\n", $file, $e->getMessage());
    }

    printf("Renamed %s to %s\n", $file, $renamed_file);
}
