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
    header("Location: leave_list_hina.php?date=".$_POST['commuting_date']);
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

      <!-- 標題和按鈕 -->
      <p class="mb-1 mt-2 fw-bold fs-2 text-center">請假記錄總表</p>
      <div class="p-2 mb-2" style="height: 8vh;display:flex;gap:0.5rem;justify-content: center;">
          <button type="button"
                  class="btn btn-outline-danger"
                  data-bs-toggle="modal"
                  data-bs-target="">
                  下載[選擇的月份]月excel
          </button>
          <button type="button"
                  class="btn btn-outline-primary"
                  data-bs-toggle="modal"
                  data-bs-target="">
                  下載今年excel
          </button>
      </div>

        <!-- 上個月 下個月 -->
        <div style="width:100%;padding: 0px;font-size: medium;position: relative;text-align: center;">
            <a href="leave_list_hina.php?date=<?=$lastDate?>"
               class="btn btn-sm btn-outline-dark"
               style="font-size:xx-small;position: absolute;left: 0;">
               ←<?=$Tools->getChineseMonthBydate($lastDate)?></a>
            <span style="height: 1rem;font-weight: bolder;">
                <?=substr($inDate,0,4)?>年 
                <?=$Tools->getChineseMonthBydate($inDate)?>
                <?php if( $authority >= 2) :?>
                  
                <?php endif ?>
            </span>
            <a href="leave_list_hina.php?date=<?=$nextDate?>"
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
        <!-- 會員請假總表 -->
      <div class="table-box" style="width: 100%; display: flex; justify-content: center;">
        <div class="mt-2 py-0 px-0" style="width: 94%; background-color: #ffffff; border: 1px solid #007180;">
            <div class="">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr align="center">
                            <th scope="col" colspan="5" style="padding: 0px;">
                                <div class="p-3"
                                    style="font-size: 1.2rem; background-color: #a1d6ed; text-align: center;">
                                    <b>[會員暱稱]</b>請假申請
                                </div>
                            </th>
                        </tr>
                        <tr align="center">
                            <th scope="col" style="color: #007180;">請假日期</th>
                            <th scope="col" style="color: #007180;">請假時間</th>
                            <th scope="col" style="color: #007180;">假別</th>
                            <th scope="col" style="color: #007180;">時數</th>
                            <th scope="col" style="color: #007180;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td align="center">[11/12(一)]</td>
                            <td align="center">[9:00~18:00]</td>
                            <td align="center"><span>[特休]</span></td>
                            <td align="center">[8]</td>
                            <td align="center">
                                <button data-bs-toggle="modal" data-bs-target="#updateLeaveModal"
                                    class="btn btn-sm btn-outline-primary" style="font-size: small; cursor: pointer;">
                                    詳內
                                </button>
                            </td>
                        </tr>
                        <tr style="border-top: 2px solid #007180;">
                            <td align="right" class="fw-bold" colspan="3" style="padding-right: 10px;">
                                本月已請假:
                            </td>
                            <td align="center" class="text-danger fw-bold" colspan="1">[8]</td>
                            <td align="center" class="text-danger fw-bold" colspan="1">小時</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
          <!--  -->
      <?php endif ?>

    </div>

    <!-- Update Modal:請假詳內 -->
    <div class="modal fade " id="updateLeaveModal" tabindex="-1" aria-labelledby="updateLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5" id="updateLeaveModalLabel">請假紀錄詳內</h1>
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
                                 v-model="pick_commuting_detail.startTime"
                                 @input="calculateHours" 
                                 value="" >
                      </div>

                      <div class="input-group mb-2">
                          <span class="input-group-text" id="basic-addon1">結束時間*</span>
                          <input name=""
                                 type="time"
                                 class="form-control" placeholder="結束時間"
                                 v-model="pick_commuting_detail.endTime"
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

    </div> 
    <!-- for VUE -->
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
            leave: '' ,// 假別
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