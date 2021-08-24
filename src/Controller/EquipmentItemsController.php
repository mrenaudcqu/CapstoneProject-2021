<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Locator\LocatorAwareTrait;

class EquipmentItemsController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        // Configure the login action to not require authentication, preventing
        // the infinite redirect loop issue
        $this->Authentication->addUnauthenticatedActions(['index', 'view']);
    }

    public function book($id = null)
    {
        $this->loadModel('LabBookings');
        $labBookings = $this->LabBookings->newEmptyEntity();
        $this->Authorization->authorize($labBookings);
        $labBookings->equipment_id = $id;
        $labBookings->staff_id = 1234;
        $labBookings->student_id = $this->request->getAttribute('identity')->getIdentifier();
        $labBookings->booking_date = FrozenTime::now();
        $labBookings->booking_status = true;
        if ($this->request->is('post')) {
            if ($this->LabBookings->save($labBookings)) {
                $this->Flash->success(__('The booking has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The booking could not be saved. Please, try again. Labbooking: ' . $labBookings));
        }
        $this->set(compact('labBookings'));
        $this->autoRender = false;
        return $this->redirect(['action' => 'index']);
    }

    //Create an array of the distinct campus's
    public function listCampus()
    {
        $query = $this->getTableLocator()->get('EquipmentItems')
                    ->find()
                    ->select(['equipment_campus'])
                    ->distinct(['equipment_campus']);
        $this->set(compact('query'));             
        $campuslist = array();
        array_push($campuslist, 'Display All');
        foreach ($query->all() as $EquipmentItems) {
            array_push($campuslist, $EquipmentItems->equipment_campus);
        } 
        return $campuslist;

    }

    public function index()
    {
        
        $this->Authorization->skipAuthorization();

        //After Post Request
        if ($this->request->is('post')){

            //Check filter type
            $selectedFilter = $this->EquipmentItems->newEmptyEntity();
            $selectedFilter = $this->EquipmentItems->patchEntity($selectedFilter, $this->request->getData());
            $filterType = $selectedFilter->filterType;

            //Run filter type function
            //Filter by equipment
            if($filterType == 'EF'){
                $filter = $selectedFilter->equipmentFilter;
                $settings = ['conditions' => array('EquipmentItems.equipment_name LIKE' => "%$filter%")];
                $EquipmentItems = $this->paginate($this->EquipmentItems, $settings);
                $this->set(compact('EquipmentItems'));

            }
            //Filter By Campus
            if($filterType == 'CF'){
                $filter = $selectedFilter->campusFilter;
                $filter = $this->filterByCampus($filter);
                $settings = ['conditions' => array('EquipmentItems.equipment_campus LIKE' => "%$filter%")];
                $EquipmentItems = $this->paginate($this->EquipmentItems, $settings);
                $this->set(compact('EquipmentItems'));
            }

            $this->LabBookings = TableRegistry::get('LabBookings');
            $labBookings = $this->LabBookings->newEmptyEntity();
            $this->set('LabBookings');
        }
        //On initial Page Startup
        else
        {
            $EquipmentItems = $this->paginate($this->EquipmentItems);
            $this->set(compact('EquipmentItems'));

            $this->LabBookings = TableRegistry::get('LabBookings');
            $this->set('LabBookings');
            $labBookings = $this->LabBookings->newEmptyEntity();
        }

        //Retrieve Campus List
        $campuslist = $this->listCampus();
        $this->set(compact('campuslist'));       
    }

    public function view($id = null)
    {
        $this->Authorization->skipAuthorization();
        $EquipmentItems = $this->EquipmentItems->get($id, [
            'contain' => [],
        ]);

        $this->set(compact('EquipmentItems'));
    }

    public function add()
    {
        $EquipmentItems = $this->EquipmentItems->newEmptyEntity();
        $this->Authorization->authorize($EquipmentItems);
        if ($this->request->is('post')) {
            $EquipmentItems = $this->EquipmentItems->patchEntity($EquipmentItems, $this->request->getData());
            if ($this->EquipmentItems->save($EquipmentItems)) {
                $this->Flash->success(__('The labequipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The equipment item could not be saved. Please, try again.'));
        }
        $this->set(compact('EquipmentItems'));
    }

    public function edit($id = null)
    {
        $EquipmentItems = $this->EquipmentItems->get($id, [
            'contain' => [],
        ]);
        $this->Authorization->authorize($EquipmentItems);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $EquipmentItems = $this->EquipmentItems->patchEntity($EquipmentItems, $this->request->getData());
            if ($this->EquipmentItems->save($EquipmentItems)) {
                $this->Flash->success(__('The equipment item has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The equipment item could not be saved. Please, try again.'));
        }
        $this->set(compact('EquipmentItems'));
    }

    public function delete($id = null)
    {
        // The delete function now unlists the equipment item rather then deleting it.
        // The index page only shows equipment that have a status of 1, pressing delete sets it to zero.
        $this->request->allowMethod(['post', 'delete']);
        $EquipmentItems = $this->EquipmentItems->get($id);
        $this->Authorization->authorize($EquipmentItems);
        $EquipmentItems->equipment_status = '0';

        if ($this->request->is(['patch', 'post', 'put'])) {
            $EquipmentItems = $this->EquipmentItems->patchEntity($EquipmentItems, $this->request->getData());
            if ($this->EquipmentItems->save($EquipmentItems)) {
                $this->Flash->success(__('The equipment item has been deleted.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The equipment item could not be deleted. Please, try again.'));
        }
        $this->set(compact('EquipmentItems'));

        return $this->redirect(['action' => 'index']);
    }

    public function filterByCampus($filter)
    {
        $this->Authorization->skipAuthorization();
        $campusFilter = null;             
        $campuslist = $this->listCampus();
        $campusFilter = $campuslist[$filter];
                    
        if($campusFilter == 'Display All')
        {
            $campusFilter = null;
        }

        return $campusFilter;
    }  
}
