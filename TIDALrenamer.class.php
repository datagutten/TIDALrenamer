<?Php
class TIDALrenamer
{
	function prepare_metadata($trackinfo,$albuminfo,$playlist=false)
	{
		if(!is_array($trackinfo) || !is_array($albuminfo))
		{
			return false;
		}
		$trackinfo['track']=$trackinfo['trackNumber'];
		$trackinfo['artist']=$trackinfo['artist']['name'];
		$trackinfo['albumyear']=date('Y',strtotime($albuminfo['releaseDate']));
		if(!$playlist)
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
			if(empty($trackinfo['year']) && preg_match('/([0-9]{4})/',$trackinfo['copyright'],$year))
				$trackinfo['year']=$year[1];
		}
		else
		{
			die("Playlists not working\n");
			$trackinfo['album']=$tracklist['title'];
			$trackinfo['track']=$trackcounter;
			$trackinfo['totaltracks']=$tracklist['numberOfTracks'];
			$trackinfo['cover']=$tracklist['image'];
			$trackinfo['compilation']=true; //Playlists are always compilations
		}
		return $trackinfo;
	}
}