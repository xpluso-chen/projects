<?php
include_once(__DIR__.'/__veryfirst.php') ;
date_default_timezone_set('Asia/Taipei');

// 檢查是否有登入session
$Tools = new Tools;
$authCheck = $Tools->checkAuth() ;

// 取得權限 authority
$authority = $Tools->getAuthority($_SESSION['userUuid']);

// 取得 POST
if(isset($_POST['exe']) && $_POST['exe']=='setCommuting'){
    // print_r($_POST);
    // 新增
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

    $DBTool = new DBTool;
    $DBTool->mainInsert( 'commutings', $inAry );
    header("Location: schedule.php?pickDate=".$_POST['commuting_date']);
} elseif(isset($_POST['exe']) && $_POST['exe']=='updateCommuting') {
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
    header("Location: schedule.php?pickDate=".$_POST['commuting_date']);
} elseif(isset($_GET['exe']) && $_GET['exe']=='delete') {
    // 更新
    $inAry = [] ;
    $inAry['is_deleted'] = 1;
    $inAry['id'] = $_GET['id'];

    $DBTool = new DBTool;
    $DBTool->mainUpdate( 'commutings', 'id', $inAry );
    header("Location: schedule.php?pickDate=".$_POST['commuting_date']);
} elseif(isset($_GET['exe']) && $_GET['exe']=='delCommLog') {
    // 更新
    $DBTool = new DBTool;

    // print_r($_GET);
    $type = $_GET['type'];
    $val = trim($_GET['val']);

    $sql = "UPDATE commutings SET is_ignore = 1
            WHERE `$type` = '$val' AND `user_uuid` = '$_SESSION[userUuid]'; ";
    $DBTool->exeSql($sql);

    header("Location: schedule.php?pickDate=".$_POST['commuting_date']);
}

// 
if(isset($_GET['date'])){
    $inDate = $_GET['date'] ;
} else {
    $inDate = date('Y-m-d') ;
}

// 
$lastDate = date('Y-m-d' , strtotime( date('Y-m-1',strtotime($inDate)).' - 1 day ' ) ) ;
$nextDate = date('Y-m-d' , strtotime( date('Y-m-t',strtotime($inDate)).' + 1 day ' ) ) ;

// 
if(isset($_GET['pickDate'])){
    $pickDate = $_GET['pickDate'] ;
} else {
    $pickDate = $inDate ;
}

// 
if(isset($_GET['type'])){
    $schedule_type = $_GET['type'] ;
} else {
    $schedule_type = 'month' ;
}

// 取得空白日曆 calendar
$Schedules = new Schedules;
$calendar = $Schedules->calendar($inDate, $schedule_type);

$startDate = current($calendar)['date'];
$endDate = end($calendar)['date'];


// page show parameter
$_page['narbar_title'] = '出勤行事曆';

// 取得出勤資料
$Commutings = new Commutings;
if($authority <= '1' ){
    $commCalendar = $Commutings->getCommCalendarByDates($startDate,$endDate,$_SESSION['userUuid']);
    $commutings = $Commutings->getCommutingsByDates($startDate,$endDate,$_SESSION['userUuid']);
} elseif ($authority >= '2' ) {
    $commCalendar = $Commutings->getCommCalendarByDates($startDate,$endDate,'all');    
    $commutings = $Commutings->getCommutingsByDates($startDate,$endDate,'all');    
}
$commCalendarJson = json_encode($commCalendar)  ;
$commCalendarJson = str_replace('\\', '\\\\', $commCalendarJson) ;

$commutingsJson = json_encode($commutings)  ;
$commutingsJson = str_replace('\\', '\\\\', $commutingsJson) ;

// 取得個人歷史客戶
$customerLog = $Commutings->getCustomers($_SESSION['userUuid']);
$customerJson = json_encode($customerLog);

// 取得 歷史客戶-地點
$locationLog = $Commutings->getLocations($_SESSION['userUuid']);
$locationJson = json_encode($locationLog);

// 取得 歷史客戶-聯絡人
$contactLog = $Commutings->getContacts($_SESSION['userUuid']);
$contactJson = json_encode($contactLog);

// 取得 歷史地點-里程
// $mileageLog = $Commutings->getMileages($_SESSION['userUuid']);
// $mileageJson = json_encode($mileageLog);

include_once('_ary_mileageLog.php');

?>
<!-- CSS -->
<?php include_once('_css.php') ?>
<!DOCTYPE html>
<html lang="en" style="width: 100vw;">
    <!-- head -->
    <?php include_once('_head.php') ?>
    <style type="text/css">
        div table th, td{
            border: 1px solid #535353;
            white-space:nowrap;
            overflow: hidden;
            background-color: #000000;
            color: #8a949c;
        }
        td.holiday, th.holiday{
            background-color: #301011;
            color: #8a949c;
        }

        td.outer{
            background-color: #41464b;
            color: #8a949c;
        }

        td.outer.holiday{
            background-color: #41464b;
            color: #8a949c;
        }

        td.today {
            background-image: linear-gradient(to bottom left, #ffffff, #0760a2);
            color: #000000;
        }

        div.pick {
            font-weight: bolder;
            border: 4px solid #0760a299;
            padding: 0px;
            z-index: 10;
        }
        td.pick {
        }

        th {
            padding: 5px;
            width: 14%;
        }
        td div {
            font-size: xx-small;
            padding: 2px;
            text-wrap:wrap;
            word-break: break-all;
        }
        td {
            padding: 0px;
            width: 100%;
            overflow: auto;
        }

        .btn-outline-dawn{
          --bs-btn-color: #0760a2;
          --bs-btn-border-color: #0760a2;
          --bs-btn-hover-color: #fff;
          --bs-btn-hover-bg: #0760a2;
          --bs-btn-hover-border-color: #0760a2;
          --bs-btn-focus-shadow-rgb: 13,110,253;
          --bs-btn-active-color: #fff;
          --bs-btn-active-bg: #0760a2;
          --bs-btn-active-border-color: #0760a2;
          --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
          --bs-btn-disabled-color: #0760a2;
          --bs-btn-disabled-bg: transparent;
          --bs-btn-disabled-border-color: #0760a2;
          --bs-gradient: none;
        }

        input{
            font-size: medium;
        }

        html, body { -webkit-text-size-adjust: none; -moz-text-size-adjust: none; -ms-text-size-adjust: none; text-size-adjust: none; }
        
        ::-webkit-scrollbar {
          width: 1px;
        }
    </style>

    <body class="back-patterns">
        <div class="p-0 container-fluid justify-content-md-center"
             style="min-height: 89vh;
                    max-width: 720px;
                    background-color: #f0f5f9;
                    position: relative;" id="app">
            <!-- navbar -->
            <?php include_once('_navbar.php') ?>

            <!-- user block -->
            <!-- schedule -->

            <!-- 上個月 下個月 -->
            <div class="py-2 px-1" >
                <div style="width:100%;padding: 0px;font-size: medium;position: relative;text-align: center;height: 28px">
                    <a href="schedule.php?date=<?=$lastDate?>"
                       class="btn btn-sm btn-outline-dark"
                       style="font-size:xx-small;position: absolute;left: 0;">
                       ←<?=$Tools->getChineseMonthBydate($lastDate)?></a>
                    <span style="height: 1rem;font-weight: bolder;">
                        <?=substr($inDate,0,4)?>年 
                        <?=$Tools->getChineseMonthByDate($inDate)?> Hina
                    </span>
                    <a href="schedule.php?date=<?=$nextDate?>"
                       class="btn btn-sm btn-outline-dark"
                       style="font-size:xx-small;float: right;position: absolute;right: 0;">
                       <?=$Tools->getChineseMonthBydate($nextDate)?>→</a>
                </div>
            </div>

            <div style="width:100%; background-color: #fffffff;">
                <table style="width:100%;table-layout:fixed" cellpadding="0"  >
                    <thead align="center"  border="1">
                        <th width="" class="holiday">日</th>
                        <th width="">一</th>
                        <th width="">二</th>
                        <th width="">三</th>
                        <th width="">四</th>
                        <th width="">五</th>
                        <th width="" class="holiday">六</th>
                    </thead>
                    <tbody  border="1">
                        <?php foreach ($calendar as $key => $cal) : ?>
                            <?php if( $key%7 == 0 ) { echo '<tr>';} ?>
                                <td class="<?=$cal['show-class']?>"
                                    v-bind:class="{ 'pick': pick_date == '<?=$cal['date']?>' }">
                                    <div class="<?=$cal['show-class']?>"
                                         v-bind:class="{ 'pick': pick_date == '<?=$cal['date']?>' }"
                                         @click="pick('<?=$cal['date']?>','<?=$cal['show-date-memo']?>')"
                                         style="height: 80px;width: 100%">
                                        <span><?=$cal['show-date']?></span><br>
                                        <?php
                                            if($cal['show-date-memo'] != '') {
                                                echo '<span style="color:#ff2300">';
                                                echo $cal['show-date-memo'].'<br>';
                                                echo '</span>';
                                            }
                                        ?>
                                        <?php
                                            if(isset($commCalendar[$cal['date']])) {
                                                $nowComm = $commCalendar[$cal['date']];
                                                foreach ( $nowComm as $key => $comm){
                                                    $tmp = '';
                                                    $tmp .= '<span style="color:#FFF">';
                                                    $tmp .= $comm['customer'];
                                                    $tmp .= '@'.$comm['location'];
                                                    $tmp .= '</span>';
                                                    $tmp .= '<br>';
                                                    echo $tmp;
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                            <?php if( $key%7 == 6 ) { echo '</tr>';} ?>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <!-- 顯示日期 -->
            <div class="p-2" style="border-bottom: 1px double #f75d5d;">
                <h3 class="p-0 m-0">
                    {{ pick_date }} {{ pick_date_memo }}
                    <span style="font-size:1rem">{{ if_today }}</span>
                </h3>
            </div>
            <div class="p-1">
                <table style="background-color:#FFF;width: 100%;">
                    <tr v-for="comm in pick_commutings"
                        class="p-1" style="background-color:#FFF;
                                           border-bottom: 1px solid #f75d5d;"
                        style="font-size:2rem">
                        <td class="p-1"
                            style="background-color:#FFF;border: 0px;width: 10%;
                                   color:#333333;font-size:1.2rem">{{comm.time}}</td>
                        <td class="py-0 px-1"
                            style="background-color:#FFF;border: 0px;width: 80%;
                                  color:#333333;font-size:1.2rem">{{comm.issue}}@{{comm.location}}({{comm.contact}})</td>
                        <td class="p-1"
                            style="background-color:#FFF;border: 0px;width: 10%;
                                   color:#333333;font-size:1.2rem">
                           <button class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="modal" data-bs-target="#updateCommutingModal"
                                   style="font-size: small;padding: 2px 4px;cursor: pointer;"
                                   @click="setUpdateModal(comm.id)">
                             詳內
                           </button>
                           <button class="btn btn-sm btn-outline-danger"
                                   style="font-size: small;padding: 2px 4px;cursor: pointer;"
                                   @click="deleteComm(comm.id)">
                             刪除
                           </button>
                       </td>
                    </tr>
                </table>
            </div>            

            <div class="p-2" style="height: 8vh;display:flex;gap:0.5rem;justify-content: center;">
                          
                <button type="button"
                        class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#setCommutingModal">
                  請假申請
                </button>
                <button type="button"
                        class="btn btn-outline-success"
                        data-bs-toggle="modal"
                        data-bs-target="#setCommutingModal2">
                  新增加班
                </button>
                <button type="button"
                        class="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#setCommutingModal">
                  新增出勤
                </button>
            </div>

            <!-- Create Modal :新增出勤紀錄-->
            <div class="modal fade "
               id="setCommutingModal" tabindex="-1" aria-labelledby="setCommutingModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                  <h1 class="modal-title fs-5" id="setCommutingModalLabel">新增出勤記錄</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <form id="create-project" method="POST" action="">
                    <input type="hidden" name="exe" value="setCommuting">
                    <input type="hidden" name="user_uuid" value="<?=$_SESSION['userUuid']?>">

                    <div class="modal-body">

                    <div class="input-group mb-2">
                      <span class="input-group-text" id="basic-addon1">事由*</span>
                      <input name="issue" type="text" class="form-control"
                             placeholder="事由" required>
                    </div>

                    <div class="input-group mb-2">
                      <span class="input-group-text" id="basic-addon1">廠商/客戶*</span>
                      <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                      </button>
                      <ul class="dropdown-menu">
                          <?php foreach ($customerLog as $key => $value) : ?>
                              <li>
                                <div class="dropdown-item">
                                    <span @click="setCustomer('<?=$value?>')"><?=$value?></span>
                                    <a style="float:right;color: red;" @click="delCommLog('customer', '<?=$value?>',$event)" >刪</a>                                    
                                </div>
                              </li>
                          <?php endforeach ?>
                      </ul>
                      <input name="customer" type="text" class="form-control"
                             placeholder="廠商/客戶" v-model="customer" required>
                    </div>

                    <!-- 地點 -->
                    <div class="input-group mb-2">
                      <span class="input-group-text" id="basic-addon1">地點*</span>
                      <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                      </button>
                      <ul class="dropdown-menu">
                        <li>
                          <div class="dropdown-item" v-for=" location in pick_location ">
                              <span @click="setLocation(location[1])">{{location[0]}}-{{location[1]}}</span>
                              <a style="float:right;color: red;" @click="delCommLog('location', location[1], $event)" >刪</a>
                          </div>
                        </li>
<!--  <li v-for=" location in pick_location ">
                            <a class="dropdown-item" href="#"@click="setLocation(location[1])">{{location[0]}}-{{location[1]}}</a>
                          </li> -->
                      </ul>
                      <input name="location" type="text" class="form-control"
                             placeholder="地點" v-model="location" required>
                    </div>

                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">聯絡人*</span>
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                              <div class="dropdown-item" v-for=" contact in pick_contact ">
                                  <span @click="setContact(contact[1])">{{contact[0]}}-{{contact[1]}}</span>
                                  <a style="float:right;color: red;" @click="delCommLog('contact', contact[1],$event)" >刪</a>
                              </div>
                            </li>
                        </ul>
                        <input name="contact" type="text" class="form-control"
                               placeholder="聯絡人" v-model="contact" required>
                      </div>

                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">拜訪日期*</span>
                        <input name="commuting_date"
                               type="date"
                               class="form-control" placeholder="拜訪日期"
                               v-model="pick_date" >
                      </div>
                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">拜訪時間*</span>
                        <input name="commuting_time"
                               type="time"
                               class="form-control" placeholder="拜訪時間"
                               value="<?=date('H:m',time())?>" >
                      </div>

                      <div class="p-2">
                          <input type="checkbox" id="isComCar" name="type" value="cc" v-model="type" >
                          <label for="isComCar">開公司車</label><br>                          
                      </div>

                      <div class="input-group mb-2" v-if="!type">
                        <span class="input-group-text" id="basic-addon1">里程*</span>
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($mileageLog as $log) : ?>
                                <li>
                                  <div class="dropdown-item">
                                      <span @click="setMileage('<?=$log[1]?>')"><?=$log[0]?></span>
                                  </div>
                                </li>
                            <?php endforeach ?>
                        </ul>

                        <input name="mileage" type="number" class="form-control"
                               min="0" max="500" step="0.1"
                               placeholder="里程" v-model="mileage" required>
                      </div>

                      <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">備註</span>
                        <textarea name="memo" type="text" class="form-control" placeholder="備註(可換行)"></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                      <input type="submit" class="btn btn-primary" value="確定新增">
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <!-- Create Modal:新增請假紀錄 -->
            <div class="modal fade "
               id="setCommutingModal2" tabindex="-1" aria-labelledby="setCommutingModalLabel2" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                  <h1 class="modal-title fs-5" id="setCommutingModalLabel2">新增請假記錄</h1>
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
                      <!--  -->
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
                        <input name="contact" type="text" class="form-control" placeholder="假別" v-model="leave" required>
                     </div>
                  
                      <!--  -->

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">請假日期*</span>
                          <input name="commuting_date"
                                 type="date"
                                 class="form-control" placeholder="請假日期"
                                 v-model="pick_date"
                                 value="" >
                        </div>

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">開始時間*</span>
                          <input name="start_time"
                                 type="time"
                                 class="form-control" placeholder="開始時間"
                                 v-model="pick_commuting_detail.startTime"
                                 @input="calculateHours" 
                                 value="" >
                        </div>

                        <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">結束時間*</span>
                          <input name="end_time"
                                 type="time"
                                 class="form-control" placeholder="結束時間"
                                 v-model="pick_commuting_detail.endTime"
                                 @input="calculateHours" 
                                 value="" >
                        </div>

                        <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">備註</span>
                          <textarea name="memo" type="text" class="form-control" placeholder="備註(可換行)" v-model="pick_commuting_detail.memo"></textarea>
                        </div>
                        <p id="result">{{ resultMessage }}</p>

                    </div>
                    </form>
                </div>
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
        </div>



        <!-- footer -->
        <?php include_once('_footer.php') ;?>

        <!-- bootstrap -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>

        <!-- vue2 -->
        <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/vue@2"></script> -->

        <!-- 自訂的js -->
        <script type="text/javascript">
            let commCals = '<?=$commCalendarJson?>';
            let commutings = '<?=$commutingsJson?>';
            let locationJson = '<?=$locationJson?>';
            let contactJson = '<?=$contactJson?>';
            let app = new Vue({
              el: '#app',
              data: {
                commCals: JSON.parse(commCals),
                commutings: JSON.parse(commutings),
                locations: JSON.parse(locationJson),
                contacts: JSON.parse(contactJson),
                // mileages: JSON.parse(mileageJson),
                pick_date: '<?=$pickDate?>',
                pick_date_memo: '',
                pick_id:'0',
                issue:'',
                pick_customer:'',
                customer:'',
                location:'',
                contact:'',
                mileage:'',
                type:false,
                leave: '' ,// 假別的值
                pick_commuting_detail: {
                    startTime: '', // 開始時間
                    endTime: ''    // 結束時間
                },
                resultMessage: '' // 結果訊息
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
                       if( this.pick_customer != '' ){
                         this.locations.forEach(function(con) {
                            console.log('con', con[0], app.pick_customer)
                           if( con[0] == app.pick_customer ){
                             tmp.push(con);
                           }
                         });
                       } else {
                           tmp = this.locations;
                       }
                        console.log('this.pick_customer',this.pick_customer)
                        console.log('tmp',tmp);
                       return tmp
                   },
                   pick_contact: function(){
                       let tmp = [];
                       if( this.pick_customer != '' ){
                         this.contacts.forEach(function(con) {
                           if( con[0] == app.pick_customer ){
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
                    this.pick_customer = e
                },
                setMileage: function(e) {
                    this.mileage = e
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
                delCommLog: function(type, val, e) {
                    if( confirm('是否確定移除快取?') ){

                        fetch('_api_delCommLog.php?type='+type+'&val='+val)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text(); // 將回應轉為 JSON
                            })
                            .then(data => {
                                e.target.closest('div.dropdown-item').remove()
                                console.log(data); // 在這裡處理回傳數據
                            })
                            .catch(error => {
                                console.error('There was a problem with the fetch operation:', error);
                            });

                        // window.location.assign('schedule.php?exe=delCommLog&type='+type+'&val='+val)
                    }
                },
                setLeave(e) {
                    this.leave = e // 假別
                },

                // 新增計算時間的方法
                calculateHours() {
                console.log('有觸發計算');
                const startTime = this.pick_commuting_detail.startTime;
                const endTime = this.pick_commuting_detail.endTime;

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
            });
        </script>
    </body>
</html>