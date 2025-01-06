<?php
include_once(__DIR__.'/__veryfirst.php') ;
// 檢查是否有登入session
$Tools = new Tools;
$authCheck = $Tools->checkAuth() ;

// 取得時間
if(isset($_GET['date'])){
    $inDate = $_GET['date'] ;
} else {
    $inDate = date('Y-m-d') ;
}

// 取得 POST
if(isset($_POST['exe']) && $_POST['exe']=='updateCommuting') {
    // 更新
    $inAry = [] ;
    $inAry['issue'] = $_POST['issue'];
    $inAry['datetime'] = $_POST['commuting_date'].' '.$_POST['commuting_time'];
    $inAry['customer'] = $_POST['customer'];
    $inAry['location'] = $_POST['location'];
    $inAry['contact'] = $_POST['contact'];
    $inAry['user_uuid'] = $_POST['user_uuid'];
    $inAry['mileage'] = $_POST['mileage'];
    $inAry['memo'] = $_POST['memo'];
    if(isset($_POST['type'])){
        $inAry['type'] = $_POST['type'];
    } else {
        $inAry['type'] = '';        
    }
    $inAry['id'] = $_POST['id'];

    $DBTool = new DBTool;
    $DBTool->mainUpdate( 'commutings', 'id', $inAry );
    header("Location: commuting_list.php?date=".$_POST['commuting_date']);
} 

// 取得GET
if(isset($_GET['status'])){
  $status = trim($_GET['status']) ;
} else {
  $status = 1 ;
}

// 取得權限 authority
$authority = $Tools->getAuthority($_SESSION['userUuid']);


// 取得上下月
$lastDate = date('Y-m-d' , strtotime( date('Y-m-1',strtotime($inDate)).' - 1 day ' ) ) ;
$nextDate = date('Y-m-d' , strtotime( date('Y-m-t',strtotime($inDate)).' + 1 day ' ) ) ;

// 取得月頭 月尾
$startDate = date('Y-m-1',strtotime($inDate));
$endDate = date('Y-m-t 23:59:59',strtotime($inDate));

// 取得出勤資料
$Commutings = new Commutings;
if($authority == '1'){
    $commutings = $Commutings->getCommutingsByDates($startDate,$endDate,$_SESSION['userUuid']);
} elseif ($authority >= '2') {
    $commutings = $Commutings->getCommutingsByDates($startDate,$endDate,'all');    
}

$commutingsJson = json_encode($commutings);
$commutingsJson = str_replace("\\r", "\\\\r", $commutingsJson);
$commutingsJson = str_replace("\\n", "\\\\n", $commutingsJson);

// commutings 排序
$showComs = [];
$statComs = [];
foreach ($commutings as $comKey => $comVal) {
  //同人合併
  $nowUuid = $comVal['user_uuid'];

  // 沒有設定就增加上去
  if( !isset( $showComs[$nowUuid] )){
    $showComs[$nowUuid] = [];
    $statComs[$nowUuid]['total'] = 0;
    $statComs[$nowUuid]['cc']= 0;
  }
  $showComs[$nowUuid][] = $comVal;
  $statComs[$nowUuid]['total'] += $comVal['mileage'];
  if( 'cc' == $comVal['type'] ){
    $statComs[$nowUuid]['cc'] += $comVal['mileage'];
  }
}

$error = '';
if( $commutings == [] ){
  if($status == 1){
    $error = '目前沒有記錄';
  }
}

// 取得歷史客戶
$customerLog = $Commutings->getCustomers($_SESSION['userUuid']);
$customerJson = json_encode($customerLog);

// 取得 歷史客戶-地點
$locationLog = $Commutings->getLocations($_SESSION['userUuid']);
$locationJson = json_encode($locationLog);

// 取得 歷史客戶-聯絡人
$contactLog = $Commutings->getContacts($_SESSION['userUuid']);
$contactJson = json_encode($contactLog);

// 取得 歷史地點-里程
$mileageLog = $Commutings->getMileages($_SESSION['userUuid']);
$mileageJson = json_encode($mileageLog);

// 取得月補貼額

$Logs = new Logs ;
$allowances = $Logs->getAllowanceByDate($inDate) ;

?>
<!-- CSS -->
<?php include_once('_css.php') ?>
<!DOCTYPE html>
<html lang="en" style="width: 100vw;">
  <!-- head -->
  <?php include_once('_head.php') ?>

  <!-- body -->
  <body class="back-patterns">
    <div id="app">  <!-- for VUE -->
    <div class="p-0 container-fluid justify-content-md-center"
       style="min-height: 89vh;
          max-width: 720px;
          position: relative;">
      <!-- navbar -->
      <?php include_once('_navbar.php') ?>

      <!-- main block -->
      <div class="py-2 px-1" style="width:100%; background-color: #fffffff0;">
        <!-- 上個月 下個月 -->
        <div style="width:100%;padding: 0px;font-size: medium;position: relative;text-align: center;">
            <a href="commuting_list.php?date=<?=$lastDate?>"
               class="btn btn-sm btn-outline-dark"
               style="font-size:xx-small;position: absolute;left: 0;">
               ←<?=$Tools->getChineseMonthBydate($lastDate)?></a>
            <span style="height: 1rem;font-weight: bolder;">
                <?=substr($inDate,0,4)?>年 
                <?=$Tools->getChineseMonthBydate($inDate)?>
                <?php if( $authority >= 2) :?>
                  
                <?php endif ?>
            </span>
            <a href="commuting_list.php?date=<?=$nextDate?>"
               class="btn btn-sm btn-outline-dark"
               style="font-size:xx-small;float: right;position: absolute;right: 0;">
               <?=$Tools->getChineseMonthBydate($nextDate)?>→</a>
        </div>
      </div>

      <?php if( $error !== ''  ) : ?>
        <div class="my-2 p-3" style="width:100%;background-color: #f0f5f9;" align="center">
            <?= $error ?>
        </div>
      <?php else : ?>
        <?php foreach ($showComs as $uuid => $showcom) : ?>
          <div class="my-2 py-0 px-0"
               style="width:100%;background-color: #f0f5f9;">
           <table id="excel-<?=$uuid?>" hidden>
             <thead>
               <tr>
                 <th>#</th>
                 <th>日期時間</th>
                 <th>客戶</th>
                 <th>記錄</th>
                 <th>里程</th>
                 <th></th>
               </tr>
             </thead>
             <tbody>
               <?php $ii=0 ;?>
               <?php foreach ($showcom as $key => $item) : ?>
                 <tr>
                   <td><?=(++$ii)?></td>
                   <td><?=$item['datetime']?></td>
                   <td><?=$item['customer']?></td>
                   <td><?=$item['issue']?>-<?=$item['contact']?>@<?=$item['location']?></td>
                   <td>
                     <?php if($item['type']=='cc') : ?>
                       (公)
                     <?php else : ?>
                       <?=$item['mileage']?>
                     <?php endif ?>
                   </td>
                   <td>
                   </td>
                 </tr>
               <?php endforeach ?>
               <tr>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td >
                   累計里程數
                 </td>
                 <td><?=$statComs[$uuid]['total']?></td>
                 <td> km</td>
               </tr>
               <tr>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td>
                   補貼金額試算
                 </td>
                 <td colspan="1">
                   <?=round($statComs[$uuid]['total']*$allowances) ?>
                 </td>
                 <td>元</td>
               </tr>
               <tr>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td>
                   (每公里補貼 <?=$allowances?> 元)</td>
                 <td></td>
               </tr>
             </tbody>
           </table>

            <table class="table table-striped table">
              <thead class="thead-dark">
                <tr align="left">
                  <th scope="col" colspan="6" style="padding:4px 0px">
                    <div class="p-3" style="font-size:1.2rem;padding-left: 1rem;background-color: #a1d6ed;">
                      <b><?=$showcom[0]['show_name']?></b>里程統計表
                      <button class="btn btn-sm btn-outline-dark p-1" onclick="makeExcel('excel-<?=$uuid?>','<?=date('Ym_').$showcom[0]['show_name']?>_里程統計表')">
                        下載excel
                      </button>
                    </div>
                  </th>
                </tr>
                <tr align="left">
                  <th scope="col">#</th>
                  <th scope="col" style="padding:4px 0px">時間</th>
                  <th scope="col" style="padding:4px">客戶</th>
                  <th scope="col" style="padding:4px 0px">記錄</th>
                  <th align="right" scope="col" style="padding:4px 0px">里程</th>
                  <th align="right" scope="col" style="padding:4px 0px">操作</th>
                </tr>
              </thead>
              <tbody>
                <?php $ii=0 ;?>
                <?php foreach ($showcom as $key => $item) : ?>
                  <tr>
                    <th scope="row"><?=(++$ii)?></th>
                    <td style="padding:4px 0px;width: 3rem;">
                      <?=substr($item['datetime'],5,5)?>
                      <br>
                      <?=substr($item['datetime'],11,5)?>
                    </td>
                    <td style="padding:4px"><?=$item['customer']?></td>
                    <td style="padding:4px 0px">
                      <span style="">
                        <?=$item['issue']?>-<?=$item['contact']?>
                        @<?=$item['location']?>
                      </span>
                    </td>
                    <td align="right" style="padding:4px 4px 0px 0px">
                      <?php if($item['type']=='cc') : ?>
                        (公)
                      <?php else : ?>
                        <?=$item['mileage']?>
                      <?php endif ?>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#updateLeaveModal"
                              style="font-size: small; padding: 2px 4px; cursor: pointer;"
                              @click="setUpdateModal('<?=$item['id']?>')">
                        詳內
                      </button>
                    </td>
                  </tr>
                <?php endforeach ?>
                <tr style="border-top:solid 2px #111111;">
                  <td align="right" style="padding:4px 10px 4px 0px" colspan="4">
                    累計里程數
                  </td>
                  <td align="right" style="padding:4px" colspan="1"><?=$statComs[$uuid]['total']?></td>
                  <td align="" style="padding:4px" colspan="1"> km</td>
                </tr>
                <tr style="">
                  <td align="right" style="padding:4px 10px 4px 0px" colspan="4">
                    補貼金額試算
                  </td>
                  <td align="right" style="padding:4px" colspan="1">
                    <?=round($statComs[$uuid]['total']*$allowances) ?>
                  </td>
                  <td align="" style="padding:4px" colspan="1">元</td>
                </tr>
                <tr style="border-top:solid 2px #111111;">
                  <td align="right" style="padding:4px 0px 4px 0px" colspan="5">
                    (每公里補貼 <?=$allowances?>
                  </td>
                  <td align="" style="padding:4px" colspan="1"> 元)</td>
                </tr>
                <?php if(false) :?>
                  <tr style="" >
                    <td align="right" style="padding:4px 10px 4px 0px" colspan="4">
                      (公司車)累計里程
                    </td>
                    <td align="right" style="padding:4px 0px" colspan="2">
                      <?=$statComs[$uuid]['cc']?> km
                    </td>
                  </tr>
                  <tr style="border-top:solid 2px #111111;">
                    <td align="right" style="padding:4px 10px 4px 0px" colspan="4">
                      (自用車)累計里程
                    </td>
                    <td align="right" style="padding:4px 0px;font-size: 1rem;" colspan="2">
                      <b>
                        <?=$statComs[$uuid]['total']-$statComs[$uuid]['cc']?> km
                      </b>
                    </td>
                  </tr>
                <?php endif?>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php endif ?>

      <!-- 按鈕 -->
      <div class="p-2" style="height: 8vh;" hidden>
          <button type="button"
                  class="btn btn-outline-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#setCommutingModal"
                  style="float:right">
            新增出勤記錄
          </button>
      </div>
    </div>

    
    <!-- Update Modal -->
    <div class="modal fade "
       id="updateCommutingModal" tabindex="-1" aria-labelledby="updateCommutingModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
          <h1 class="modal-title fs-5" id="updateCommutingModalLabel">出勤記錄詳內</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="create-project" method="POST" action="">
            <input type="hidden" name="exe" value="updateCommuting">
            <input type="hidden" name="user_uuid" value="<?=$_SESSION['userUuid']?>">
            <input type="hidden" name="id"
                   v-model="pick_commuting_detail.id">

            <div class="modal-body">

                <div class="input-group mb-2">
                    <span class="input-group-text" id="basic-addon1">出勤人員</span>
                    <input type="text" class="form-control"
                           placeholder="出勤人員" v-model="pick_commuting_detail.show_name" disabled>
                </div>

                <div class="input-group mb-2">
                    <span class="input-group-text" id="basic-addon1">事由*</span>
                    <input name="issue" type="text" class="form-control"
                           placeholder="事由" v-model="pick_commuting_detail.issue" required>
                </div>

                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">廠商/客戶*</span>
                  <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu">
                      <?php foreach ($customerLog as $key => $value) : ?>
                          <li><a class="dropdown-item" href="#"
                                 @click="setCustomer('<?=$value?>')"><?=$value?></a></li>
                      <?php endforeach ?>
                  </ul>
                  <input name="customer" type="text" class="form-control"
                         placeholder="廠商/客戶" v-model="pick_commuting_detail.customer" required>
                </div>

                <!-- 地點 -->
                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">地點*</span>
                  <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu">
                      <li v-for=" location in pick_location ">
                        <a class="dropdown-item" href="#"
                                 @click="setLocation(location[1])">{{location[0]}}-{{location[1]}}</a>
                      </li>
                  </ul>
                  <input name="location" type="text" class="form-control"
                         placeholder="地點" v-model="pick_commuting_detail.location" required>
                </div>

                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">聯絡人*</span>
                  <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu">
                      <li v-for=" contact in pick_contact ">
                        <a class="dropdown-item" href="#"
                                 @click="setContact(contact[1])">{{contact[0]}}-{{contact[1]}}</a>
                      </li>
                  </ul>
                  <input name="contact" type="text" class="form-control"
                         placeholder="聯絡人" v-model="pick_commuting_detail.contact" required>
                </div>

                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">拜訪日期*</span>
                  <input name="commuting_date"
                         type="date"
                         class="form-control" placeholder="拜訪日期"
                         v-model="pick_commuting_detail.date" >
                </div>
                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">拜訪時間*</span>
                  <input name="commuting_time"
                         type="time"
                         class="form-control" placeholder="拜訪時間"
                         v-model="pick_commuting_detail.time" 
                         value="<?=date('H:m',time())?>" >
                </div>

                <div class="p-2">
                    <input type="checkbox" id="isComCarUpdate" name="type" value="cc" 
                           v-model="pick_commuting_detail.type">
                    <label for="isComCarUpdate">開公司車</label><br>                          
                </div>

                <div class="input-group mb-2" v-if="!pick_commuting_detail.type">
                  <span class="input-group-text" id="basic-addon1">里程*</span>
                  <input name="mileage" type="number" class="form-control"
                         min="0" max="500" step="0.1"
                         placeholder="里程" v-model="pick_commuting_detail.mileage" required>
                </div>

                <div class="input-group mb-2">
                  <span class="input-group-text" id="basic-addon1">備註</span>
                  <textarea name="memo" type="text" class="form-control" placeholder="備註(可換行)" v-model="pick_commuting_detail.memo"></textarea>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-danger" @click="deleteComm(pick_commuting_detail.id)">刪除</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
              <input type="submit" class="btn btn-primary" value="確定修改">
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Update Modal:請假詳內 -->
    <div class="modal fade " id="updateLeaveModal" tabindex="-1" aria-labelledby="updateLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5" id="updateLeaveModalLabel">新增請假申請</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="create-project" method="POST" action="">
                  <input type="hidden" name="exe" value="setCommuting">
                  <input type="hidden" name="user_uuid" value="<?=$_SESSION['userUuid']?>">

                  <div class="modal-body">
                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">請假人員</span>
                        <input type="text" class="form-control" placeholder="請假人員" v-model="pick_commuting_detail.show_name" disabled>
                      </div>
                      
                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">假別*</span>
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                          <li class="p-2" @click="setLeave('特休')">特休</li>
                          <li><hr class="dropdown-divider"></li>
                          <li class="p-2" @click="setLeave('事假')">事假</li>
                          <li><hr class="dropdown-divider"></li>
                          <li class="p-2" @click="setLeave('病假')">病假</li>
                          <li><hr class="dropdown-divider"></li>
                          <li class="p-2" @click="setLeave('補休')">補休</li>
                          <li><hr class="dropdown-divider"></li>
                          <li class="p-2" @click="setLeave('其他')">其他</li>
                        </ul>
                        <input name="" type="text" class="form-control" placeholder="假別" v-model="leave" required>
                      </div>
                    
                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">請假日期*</span>
                          <input name=""
                                 type="date"
                                 class="form-control" placeholder="請假日期"
                                 v-model="pick_date"
                                 value="" >
                      </div>

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">開始時間*</span>
                          <input name=""
                                 type="time"
                                 class="form-control" placeholder="開始時間"
                                 v-model="pick_startTime"
                                 @input="calculateHours" 
                                 value="" >
                      </div>

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">結束時間*</span>
                          <input name=""
                                 type="time"
                                 class="form-control" placeholder="結束時間"
                                 v-model="pick_endTime"
                                 @input="calculateHours" 
                                 value="" >
                      </div>

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">備註</span>
                          <textarea name="" type="text" class="form-control" placeholder="備註(可換行)" v-model="pick_commuting_detail.memo"></textarea>
                      </div>
                      <p id="result">{{ resultMessage }}</p>

                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-danger" @click="deleteComm(pick_commuting_detail.id)">刪除</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                      <input type="submit" class="btn btn-primary" value="確定修改">
                    </div>
                    </form>
                </div>
              </div>
            </div>

    </div>  <!-- for VUE -->
    <!-- footer -->
    <?php include_once('_footer.php') ;?>

    <!-- bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>

    <!-- vue2 -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/vue@2"></script> -->

    <!-- 下載 excel 用 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- 自訂的js -->
    <script type="text/javascript">
        let commutings = '<?=$commutingsJson?>';
        let locationJson = '<?=$locationJson?>';
        let contactJson = '<?=$contactJson?>';
        let mileageJson = '<?=$mileageJson?>';
        let app = new Vue({
          el: '#app',
          data: {
            commutings: JSON.parse(commutings),
            locations: JSON.parse(locationJson),
            contacts: JSON.parse(contactJson),
            mileages: JSON.parse(mileageJson),
            pick_date_memo: '',
            pick_id:'0',
            issue:'',
            customer:'',
            location:'',
            contact:'',
            mileage:'',
            type:false,
            pick_startTime,
            pick_endTime
          },
          computed: {
              pick_commutings: function () {
                  if (this.commCals[this.pick_date] === undefined) {
                    return [] ;
                  } else {
                    return this.commCals[this.pick_date]
                  }
              },
              pick_commuting_detail: function () {
                  if (this.commutings[this.pick_id] === undefined) {
                    return [] ;
                  } else {
                    return this.commutings[this.pick_id]
                  }
              },
              if_today: function(){
                  if( this.pick_date == '<?=date('Y-m-d')?>' ){
                    return '(今天)';
                  } else {
                    return '';
                  }
               },
               pick_location: function(){
                   let tmp = [];
                   let nowCustomer;
                   if( this.pick_commuting_detail.customer == undefined){
                     nowCustomer = this.customer
                   } else {
                     nowCustomer = this.pick_commuting_detail.customer;
                   }
                   console.log(nowCustomer)
                   if( nowCustomer != '' ){
                     this.locations.forEach(function(con) {
                       if( con[0] == nowCustomer ){
                         tmp.push(con);
                       }
                     });
                   } else {
                       tmp = this.locations;
                   }
                   return tmp
               },
               pick_contact: function(){
                   let tmp = [];

                   let nowCustomer;
                   if( this.pick_commuting_detail.customer == undefined){
                     nowCustomer = this.customer
                   } else {
                     nowCustomer = this.pick_commuting_detail.customer;
                   }

                   if( nowCustomer != '' ){
                     this.contacts.forEach(function(con) {
                       if( con[0] == nowCustomer ){
                         tmp.push(con);
                       }
                     });
                   } else {
                       tmp = this.contacts;
                   }
                   return tmp
               }
           },
          methods: {
            pick: function(indate,dateMemo) {
              this.pick_date = indate
              this.pick_date_memo = dateMemo
            },
            setUpdateModal: function(id){
              this.pick_id = id
            },
            setCustomer: function(e) {
                console.log(e)
                this.customer = e
                this.pick_commuting_detail.customer = e
            },
            setLocation: function(e) {
                this.location = e
                this.pick_commuting_detail.location = e
            },
            setContact: function(e) {
                this.contact = e
                this.pick_commuting_detail.contact = e
            },
            setGoalLocation: function(e) {
                this.goal_location = e
                this.pick_commuting_detail.goal_location = e
            },
            deleteComm: function(id) {
                if( confirm('是否確定永久刪除?') ){
                    window.location.assign('schedule.php?exe=delete&id='+id)
                }
            },
            setLeave(e) {
                    this.leave = e // 假別
            },
            // 新增計算時間的方法
            calculateHours() {
                console.log('有觸發計算');
                const startTime = this.pick_startTime;
                const endTime = this.pick_endTime;

                if (!startTime || !endTime) {
                    this.resultMessage = "請輸入完整的時間！";
                    return;
                }

                // 解析時間
                const [startHour, startMinute] = startTime.split(":").map(Number);
                const [endHour, endMinute] = endTime.split(":").map(Number);

                const startDate = new Date();
                startDate.setHours(startHour, startMinute, 0);

                const endDate = new Date();
                endDate.setHours(endHour, endMinute, 0);

                // 計算時間差（毫秒）
                const timeDiff = endDate - startDate;

                if (timeDiff < 0) {
                    this.resultMessage = "結束時間不能早於開始時間！";
                    return;
                }

                // 轉換為小時
                const totalHours = timeDiff / (1000 * 60 * 60);
                this.resultMessage = `共計時數: ${totalHours.toFixed(1)} 小時`;
                }
          }
        })

        

    </script>
  </body>
</html>