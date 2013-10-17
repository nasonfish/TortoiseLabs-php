<?php
class TortoiseLabs{

    public $vps;
    public $support;
    public $invoice;
    public $dns;

    private $username;
    private $password;

    /**
     * Create a new API Object using your username and API key.
     * @param string $username Your Username
     * @param string $key Your API key from http://manage.tortois.es
     */
    public function __construct($username = '', $key = ''){
        $this->username = $username;
        $this->password = $key;
        $this->vps = new VPS($this);
        $this->support = new Support($this);
        $this->invoice = new Invoice($this);
        $this->dns = new DNS($this);
    }

    /**
     * Send a request to $file at $location using the specified method and params. This returns the contents of the page.
     *
     * Normally, you shouldn't use this, and can instead use the public field classes to send requests to TortoiseLabs' API.
     *
     * @param string $file Name of the file - start this with a /.
     * @param bool $auth If you want to send your authentication username and key with the request with basic authentication.
     * @param string $method The method you want to use - GET or POST.
     * @param array $params an accociative array of values you want to GET/POST.
     * @param string $location The base location you're sending the request to. This is set up to use SSL (HTTPS)
     * @return string Data given by the page.
     */
    public function sendRequest($file, $auth = true, $method = 'GET', $params = array(), $location = 'https://manage.tortois.es'){
        $method = strtoupper($method);
        if($method === 'GET'){
            $p = '';
            foreach($params as $key => $val){
                if($p === ''){
                    $p .= urlencode($key) . '=' . urlencode($val);
                } else {
                    $p .= '&' . urlencode($key) . '=' . urlencode($val);
                }
            }
        } else {
            $p = http_build_query($params);
        }
        $headers = array();
        if($auth){
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
        }
        if($method === "POST"){
            $headers[] = "Content-type: application/x-www-form-urlencoded";
            $headers[] = 'Content-Length: ' . strlen($p);
        }
        $options = array(
            'http' => array_merge(array(
                'method' => $method,
                'header' => implode("\r\n", $headers)
            ), $method === 'POST' ? array('content' => $p) : array()) // Yuck. :(
        );
        $context = stream_context_create($options);

        $r = fopen($location . $file . ($method === 'GET' && $p != '' ? '?' . $p : ''), 'r', false, $context); // Also yuck.
        $data = stream_get_contents($r);
        fclose($r);
        return $data;
    }

    public function json_get($file){
        return json_decode($this->sendRequest($file), true);
    }
    public function json_post($file, $params){
        return json_decode($this->sendRequest($file, true, 'POST', $params), true);
    }
    public function action_post($file, $params){
        $this->sendRequest($file, true, 'POST', $params);
        return true;
    }
}

class VPS {

    private $tl;

    public function __construct(TortoiseLabs $tl){
        $this->tl = $tl;
    }

    /**
     * List all VPS associated with an account.
     *
     * Renamed from list because 'list' is a reserved word.
     */
    public function list_all(){
        return $this->tl->json_get('/vps/list');
    }

    /**
     * List all of your vps', as array(id => name)
     *
     * @return Array array of $myarray[vps_id] = vps_name
     */
    public function list_my(){
        $return = array();
        $list = $this->list_all();
        foreach($list['vpslist'] as $vps){
            $return[$vps['id']] = $vps['name'];
        }
        return $return;
    }

    /**
     * Return the available regions and plans for creating a new VPS
     * @return array http://wiki.tortois.es/index/API#.2Fvps.2Fsignup regions, plans
     */
    public function signup_available(){
        return $this->tl->json_get('/vps/signup');
    }

    /**
     * Create a new VPS, using the plan and region ids from signup_available
     * @param $plan int id of plan
     * @param $region int id of region
     * @return bool success
     */
    public function signup($plan, $region){
        return $this->tl->action_post('/vps/signup', array('plan'=>$plan,'region'=>$region));

    }

    /**
     * Get information about your VPS
     * @param $id int The ID of the vps
     * @return array http://wiki.tortois.es/index/API#.2Fvps.2F.3Cid.3E vps information
     */
    public function info($id){
        return $this->tl->json_get('/vps/' . $id . '');
    }

    /**
     * Get available templates for a VPS
     * @param $id int The id of the VPS
     * @return array array of template names, and services
     */
    public function deploy_templates($id){
        return $this->tl->json_get('/vps/' . $id . '/deploy');
    }

    /**
     * (Re)image a VPS
     * @param $id int ID of the VPS you're imaging
     * @param $image string The image you're installing
     * @param $pass string Your new Root Password
     * @param $arch string Your Arch.
     * @return array Jobs
     */
    public function deploy($id, $image, $pass, $arch){
        return $this->tl->json_post('/vps/' . $id . '/deploy', array('imagename'=>$image,'rootpass'=>$pass,'arch'=>$arch));
    }

    /**
     * Set the Nickname of the VPS
     * @param $id int ID of the vps you want to set the nickname of
     * @param $nickname string The new nickname of the server
     * @return array nickname (Friendly name), name
     */
    public function setNickname($id, $nickname){
        return $this->tl->json_post('/vps/' . $id . '/setnickname', array('nickname'=>$nickname));
    }

    /**
     * Enable Watchdog monitoring
     * @param $id int The ID of the vps you're watching
     * @return array VPS Info
     */
    public function monitoring_enable($id){
        return $this->tl->json_get('/vps/' . $id . '/monitoring/enable');
    }

    /**
     * Disable Watchdog monitoring
     * @param $id int The ID of the VPS you want to stop watching
     * @return bool if the response was successful
     */
    public function monitoring_disable($id){
        return $this->tl->json_get('/vps/' . $id . '/monitoring/disable');
    }

    /**
     * List available ISOs to attach to your VPS
     * @param $id int the ID of the VPS
     * @return array The available ISOs for HVM
     */
    public function hvm($id){
        return $this->tl->json_get('/vps/' . $id . '/hvm');
    }

    /**
     * Attach an ISO to your VPS
     * @param $id int your VPS id
     * @param $iso int ISO id
     * @return array The available ISOs for HVM
     */
    public function hvm_setiso($id, $iso){
        return $this->tl->json_get('/vps/' . $id . '/hvm/setiso', array('isoid'=>$iso));
    }

    /**
     * Set the boot order for your VPS
     * @param $id int Your VPS id
     * @param $bootorder string Boot order - 'cd' = Disk image, then ISO image. 'dc' = ISO image, then Disk image. 'c' = Disk Image only. 'd' = ISO image only.
     * @return array The available ISOs for HVM
     */
    public function hvm_setBootOrder($id, $bootorder){
        return $this->tl->json_post('/vps/' . $id . '/hvm/setbootorder', array('bootorder'=>$bootorder));
    }

    /**
     * Set the NIC type for your VPS
     * @param $id int VPS ID
     * @param $nicktype string NIC type (e1000, virtio-net, rtl8139)
     * @return array The available ISOs for HVM
     */
    public function hvm_setNicType($id, $nicktype){
        return $this->tl->json_post('/vps/' . $id . '/hvm/setnictype', array('nicktype'=>$nicktype));
    }

    /**
     * Add a custom ISO to be used on your VPS
     * @param $id int VPS id
     * @param $name string Name of your new ISOThe available ISOs for HVM
     * @param $uri string The URI of the ISO
     * @return array The available ISOs for HVM
     */
    public function hvmISO_new($id, $name, $uri){
        return $this->tl->json_post('/vps/' . $id . '/hvmiso/new', array('isoname'=>$name,'isouri'=>$uri));
    }

    /**
     * Delete a custom ISO
     * @param $id int VPS id
     * @param $isoid int
     * @return array The available ISOs for HVM
     */
    public function hvmISO_delete($id, $isoid){
        return $this->tl->json_get('/vps/' . $id . '/hvmiso/' . $isoid . '/delete');
    }

    /**
     * Start your VPS!
     * @param $id int VPS id that you're starting
     * @return string Job
     */
    public function create($id){
        return $this->tl->json_get('/vps/' . $id . '/create');
    }

    /**
     * Gracefully shutdown your VPS
     * @param $id int VPS id
     * @return int Job
     */
    public function shutdown($id){
        return $this->tl->json_get('/vps/' . $id . '/shutdown');
    }

    /**
     * Forcefully shutdown a VPS
     * @param $id int VPS id
     * @return int Job
     */
    public function destroy($id){
        return $this->tl->json_get('/vps/' . $id . '/destroy');
    }

    /**
     * Power Cycle your VPS
     * @param $id int VPS id
     * @return int Job
     */
    public function powerCycle($id){
        return $this->tl->json_get('/vps/' . $id . '/powercycle');
    }

    /**
     * Return the current status of your VPS
     * @param $id int VPS id
     * @return array ("running" => boolean)
     */
    public function status($id){
        return $this->tl->json_get('/vps/' . $id . '/status.json');
    }

    /**
     * List all jobs accociated with a vps
     * @param $id int VPS id
     * @return array All Jobs
     */
    public function jobs($id){
        return $this->tl->json_get('/vps/' . $id . '/jobs.json');
    }
}
class Support {
    private $tl;

    public function __construct(TortoiseLabs $tl){
        $this->tl = $tl;
    }
}
class Invoice {
    private $tl;

    public function __construct(TortoiseLabs $tl){
        $this->tl = $tl;
    }
}
class DNS {
    private $tl;

    public function __construct(TortoiseLabs $tl){
        $this->tl = $tl;
    }
}
