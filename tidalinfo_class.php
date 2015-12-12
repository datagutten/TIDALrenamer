<?Php
class tidalinfo
{
	public $token;
	public $countryCode='NO';
	public $ch;
	function init_curl()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0');
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($this->ch,CURLOPT_ENCODING,'gzip');
		/*curl_setopt($this->ch,CURLOPT_PROXY,'192.168.1.112');
		curl_setopt($this->ch,CURLOPT_PROXYPORT,8888);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER,false);*/
	}
	function query($url,$postfields=false)
	{
		if(!is_resource($this->ch))
			$this->init_curl();
		if(empty($url))
			throw new Exception('Missing URL');
		if($postfields===false)
			curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		else
			curl_setopt($this->ch,CURLOPT_POSTFIELDS,http_build_query($postfields));
		curl_setopt($this->ch,CURLOPT_URL,$url);
		$data=curl_exec($this->ch);
		if($data===false)
		{
			$this->error='cURL error: '.curl_error($this->ch);
			return false;
		}
		else
			return $data;
	}
	function parse_response($data)
	{
		if($data===false)
			return false;
		$info=json_decode($data,true);
		if(isset($info['cover']))
			$info['cover']='http://resources.wimpmusic.com/images/'.str_replace('-','/',$info['cover']).'/640x640.jpg';
		elseif(isset($info['image']))
			$info['image']='http://resources.wimpmusic.com/images/'.str_replace('-','/',$info['image']).'/640x428.jpg';

		if(isset($info['userMessage']))
		{
			$this->error=$info['userMessage'];
			return false;
		}
		else
			return $info;
	}
	function get_token()
	{
		preg_match('/this.token="(.+)"/U',$data,$token);
		return $token[1];
	}
	function api_request($topic,$id,$field='',$url_extra='')
	{
		//Topic can be: albums, tracks, playlists
		//Field can be: tracks, contributors or empty

		//Can use sessionId or token
		if(empty($this->token))
			$this->token=$this->get_token();

		$url=sprintf('http://api.tidalhifi.com/v1/%s/%s/%s?token=%s&countryCode=%s%s',$topic,$id,$field,$this->token,$this->countryCode,$url_extra);
		return $this->parse_response($this->query($url));	
	}

	function album($id,$tracks=false)
	{
		if(empty($this->token))
			$this->token=$this->get_token();

		//$url=sprintf('http://api.tidalhifi.com/v1/albums/%s/tracks?token=%s&countryCode=%s',$id,$this->token,$this->countryCode);
		if($tracks)
			$field='tracks';
		else
			$field='';

		return $this->api_request('albums',$id,$field);
	}
	function track($id)
	{
		return $this->api_request('tracks',$id,'');
	}
	function playlist($id)
	{
 		$playlist_info=$this->api_request('playlists',$id);
		$limit=ceil($playlist_info['numberOfTracks']/100)*100;
 		$playlist_tracks=$this->api_request('playlists',$id,'tracks',"&limit=$limit&orderDirection=ASC");
		return array_merge($playlist_info,$playlist_tracks);
	}
}