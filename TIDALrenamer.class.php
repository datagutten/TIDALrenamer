<?Php
class TIDALrenamer
{
    /**
     * @var string Folder for files with id as file name
     */
    public $input_path_id;
    /**
     * @var string Folder for files with playlist or album position as file name
     */
    public $input_path_order;
    /**
     * @var string Path for renamed files
     */
    public $output_path;

    function __construct()
    {
        $config = require 'config.php';
        foreach (array('input_path_id', 'input_path_order', 'output_path') as $key)
        {
            if(!isset($config[$key]))
                throw new InvalidArgumentException("Config missing $key");
            $this->$key = $config[$key];
        }
    }

    /**
     * Prepare metadata from TIDAL to be passed to AudioMetadata methods
     * @param array $trackinfo
     * @param array $albuminfo
     * @param bool $playlist
     * @return array
     */
    public static function prepare_metadata($trackinfo, $albuminfo, $playlist = false)
    {
        if (!is_array($trackinfo) || !is_array($albuminfo)) {
            throw new InvalidArgumentException('Track info or album info not array');
        }
        $trackinfo['track'] = $trackinfo['trackNumber'];
        $trackinfo['artist'] = $trackinfo['artist']['name'];
        $trackinfo['albumyear'] = date('Y', strtotime($albuminfo['releaseDate']));
        if (!$playlist) {
            $trackinfo['album'] = $trackinfo['album']['title'];
            $trackinfo['albumartist']   = $albuminfo['artist']['name'];
            $trackinfo['tracknumber']   = $trackinfo['trackNumber'];
            $trackinfo['volumenumber']  = $trackinfo['volumeNumber'];
            $trackinfo['totaltracks']   = $albuminfo['numberOfTracks'];
            $trackinfo['totalvolumes']  = $albuminfo['numberOfVolumes'];
            $trackinfo['cover'] = $albuminfo['cover'];
            if ($albuminfo['artist']['id'] == 2935) //If album artist is "Various Artists" the album is a compilation
                $trackinfo['compilation'] = true;
            if (empty($trackinfo['year']) && preg_match('/([0-9]{4})/', $trackinfo['copyright'], $year))
                $trackinfo['year'] = $year[1];
        } else {
            //TODO: Playlist renaming
            die("Playlists not working\n");
            $trackinfo['album']         = $tracklist['title'];
            $trackinfo['track']         = $trackcounter;
            $trackinfo['totaltracks']   = $tracklist['numberOfTracks'];
            $trackinfo['cover']         = $tracklist['image'];
            $trackinfo['compilation']   = true; //Playlists are always compilations
        }
        return $trackinfo;
    }
}