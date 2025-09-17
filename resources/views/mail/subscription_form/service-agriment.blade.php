<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
table, th, td {
  border: 1px solid #747474;
  border-collapse: collapse;
  padding: 5px;
}
tr td:first-child{
    font-weight: bold;
}
tr td:nth-child(3){
    font-weight: bold;
}
    </style>
</head>
<body>
    <div class="main_content" style="
    display: flex;
    justify-content: center;
    margin: 20px 0;
">
    
        <div class="main_center" style="width: 80%">
           <div class="content_title" style=" text-align: center; ">
               <h1 style=" margin: 10px 0; text-decoration: underline; ">Service Agrement Of Academy</h1>
               <small>[An Education Institute Management Software]</small>
           </div>
           <div class="content_f_section" style="margin: 25px 0;">
            <div class="card" style="background: #fff">
                <div class="card-header" style="
                text-align: center;
                background: #C6D9F9;
                padding: 1px 0;
                font-size: 18px;
                font-weight: bold;
            ">
                    <p>1st Pary (Automate IT Limited)</p>
                </div>
                <div class="card-body">
                    <table style="width:100%">
                        <tr>
                          <td>Partner Code:</td>
                          <td>
                        {{$partner_info->partner_code}}    
                        </td> 
                          <td>Name:</td>
                          <td>    {{$partner_info->partner_name}}  </td>
                        </tr>
                        <tr>
                          <td>Mobile Number:</td>
                          <td> {{$partner_info->mobile}}</td> 
                          <td>Email:</td>
                          <td> {{$partner_info->email}}</td>
                        </tr>
                        <tr>
                          <td>Upaliza/ Thana:</td>
                          <td>{{$partner_info->upazila_thana}}</td> 
                          <td>District:</td>
                          <td>{{$partner_info->district}}</td>
                        </tr>
                       
                      </table>
                </div>
            </div>
           </div>

           <div class="content_f_section" style="margin: 25px 0;">
            <div class="card" style="background: #fff">
                <div class="card-header" style="
                text-align: center;
                background: #C6D9F9;
                padding: 1px 0;
                font-size: 18px;
                font-weight: bold;
            ">
                    <p>2nd Pary (Institute)</p>
                </div>
                <div class="card-body">
                    <table style="width:100%">
                        <tr>
                          <td>Name Of Institute:</td>
                          <td colspan="3">
                        {{$data['institute_name']}}    
                        </td> 
                         
                        </tr>
                        <tr>
                          <td>Name Of Authority:</td>
                          <td colspan="3">{{$data['authority_name']}}</td> 
                         
                        </tr>
                        <tr>
                          <td>Designation:</td>
                          <td>{{$data['authority_designation'] ?? null}}</td> 
                          <td>Mobile No:</td>
                          <td>{{$data['authority_mobile'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>Telephone:</td>
                          <td>{{$data['telephone'] ?? null}}</td> 
                          <td>Email:</td>
                          <td>{{$data['email'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>Chairman of Governing Body:</td>
                          <td>{{$data['chairman_name'] ?? null}}</td> 
                          <td>Mobile No:</td>
                          <td>{{$data['chairman_mobile'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>ICT In-charge:</td>
                          <td>{{$data['ict_in_charge'] ?? null}}</td> 
                          <td>Mobile No:</td>
                          <td>{{$data['ict_in_charge'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>Address:</td>
                          <td>{{$data['address'] ?? null}}</td> 
                          <td>Upaliza/ Thana:</td>
                          <td>{{$data['upazila_thana'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>District:</td>
                          <td>{{$data['district'] ?? null }}</td> 
                          <td>Division:</td>
                          <td>{{$data['division'] ?? null}}</td>
                        </tr>
                        <tr>
                          <td>Institute Type:</td>
                          <td>{{$data['institute_type'] ?? null }}</td> 
                          <td>Education Board:</td>
                          <td>{{$data['education_board'] ?? null}}</td>
                        </tr>
                     
                       
                      </table>
                </div>
            </div>
           </div>

           <!--price panel -->
           <div class="content_f_section" style="margin: 25px 0;">
            <div class="card" style="background: #fff">
                <div class="card-header" style="
                text-align: center;
                background: #C6D9F9;
                padding: 1px 0;
                font-size: 18px;
                font-weight: bold;
            ">
                    <p>Price Plan (Package Wise)</p>
                </div>
                <div class="card-body">
                    <table style="width:100%">
                        <tr>
                          <td>Module List:</td>
                          <td colspan="3" > 
                            <ul>
                                @foreach($module_list as $list)
                                <li>{{$list->module_name}}</li>
                                @endforeach
                                
                            </ul>
                            </td> 
                         
                        </tr>
                        <tr>
                          <td>No. Of Student:</td>
                          <td >{{$data['student_quantity'] ?? 0}}</td> 
                          <td>No. Of Teacher & Stuff:</td>
                          <td >{{$data['hr_number_quantity'] ?? null}}</td> 
                         
                        </tr>
                        <tr>
                            <td colspan="4">
                                Note: Minimum no. of students should be 300 by default. Even if, no. of student go down, it will actually be counted as 300.
                            </td>
                        </tr>
                        <tr>
                          <td>Payment Type:</td>
                          <td>{{$data['payment_type'] ?? null}}</td> 
                          <td>Service Charge:</td>
                          <td>{{$data['service_rate']}} BDT</td>
                        </tr>
                       
                        <tr>
                          <td>Total Service Charge:</td>
                          <td colspan="3">{{number_format((float)$data['service_rate'] * $data['student_quantity'],2,'.','') }} BDT</td> 
                      
                        </tr>
                        <tr>
                            <td>Domain/Hosting Renew Charge:</td>
                            <td>{{$data['domain_review_charge'] ?? null }}</td> 
                            <td>Registration Charge:</td>
                            <td>{{$data['registration_charge'] ?? null}}</td>
                          </tr>
                       
                        <tr>
                            <td>Last date of data Submission:</td>
                            <td>{{\Carbon\Carbon::parse($data['data_submission_date'])->format('d/m/Y')}}</td> 
                            <td>Handover Date::</td>
                            <td>{{\Carbon\Carbon::parse($data['tentative_handover_date'])->format('d/m/Y')}}</td>
                          </tr>
                       
                      </table>
                </div>
            </div>
           </div>
           <div class="instruction">
            <p>
                [By agreeing to the proposal of the 1st party, the 2nd party having thoroughly reviewed the presentation of the 1st party's Online Based Educational Institution Management Software (Academy-Institute Management System), understanding each other's content, the 1st and the 2nd party enter into the following agreement with each other on the basis of the following agreement. (Academy-Institute Management System) User Agreement.
            </p>

            <div class="term_condition">
                <h2 style="text-align: center;text-decoration: underline;">
                    Terms and Conditions
                </h2>
                <p>1. 1st Party shall mean “Automate IT Limited” and the 2nd Party shall mean “Educational Institution”</p>
                <p>
                    2. The 2nd party can take advantage of changing the current package and migrating to a higher package at any time. In that case, the contract has to be changed and a new contract has to be executed under the new package and the monthly service charge per student will be determined at the current market price of the company.
                </p>
                <p>
                    3. The 2nd party shall convey all the information to the 1st party as per the format prescribed by the 1st party within the specified date and the printed copies shall be signed by the concerned authorities of the 2nd party. Note that information provided by the 2nd party after the due date shall not relate to the date of software handover.
                </p>
                <p>
                    4. According to the demand and supply of information from the 2nd party - the 1st party will enter the mentioned data in the software including class, shift, section-wise student name, roll, gender, religion, father's name, mother's name, guardian's mobile number in the software and the students Detailed information will be explained to the 2nd party with storage options so that the 2nd party can insert the information as required. At the same time, the software will enter the names, gender, religion, designation, mobile numbers of the teachers and share the details to the 2nd party with the option to save the details.
                </p>
                <p>
                    5.If the 2nd party wants to use the time attendance machine, the 1st party will arrange the integration with the software. Even if the said machine has been supplied by the 1st party, the 2nd party cannot withhold any bill for software or any other service due to any defect in the machine.
                </p>
                <p>
                    6. If the 2nd party wants to use the time attendance machine, the 1st party will arrange the integration with the software. Even if the machine has been supplied by the 1st party, the 2nd party cannot withhold any bill for software or any other service due to any defect in the machine.
                </p>
                <p>
                    7. The 1st party shall change its admin panel password for security and privacy while conveying full possession of the software to the 2nd party. If the customer provides the password to the 1st party in order to receive the service, the 2nd party will change the password again immediately after receiving the service.
                </p>
                <p>
                    8. As the important data of the 2nd party will be stored on the online server under the control of the 1st party. Therefore, the security and backup of all the important data of the 2nd party will be kept by the 1st party and will be obliged to provide the requested data to the 2nd party. The 2nd party will be able to download, print, copy etc. easily at any time and save all his data as per the requirement of the 2nd party. It should be noted that if the ID is closed for more than 3 months without any application, all data will be deleted from the 1st party server. In that case, any claim or objection of the 2nd party will not be accepted.
                </p>
                <p>
                    9. The 1st party shall execute the complete software development work based on the information provided by the 2nd party within the stipulated date and provide necessary training to the personnel appointed by the 2nd party step by step.
                </p>
                <p>
                    10. Use of Academy-Institute Management System, rental charges of high-quality online based dedicated server, maintenance charges, security (protection against hacking, malware, spyware, viruses etc.) back-up server rental charges, support-services, constant development of new modules in software and For updates etc. the 2nd party shall pay service charges to the 1st party at the prescribed rate for each student. If the number of students is more or less, the service charge will be more or less. 2nd party can see the number of current students on software dashboard.
                </p>
                <p>
                    11. The prescribed service charges must be paid by the 10th of the current month, otherwise the 1st party will not be responsible if the payment of service charges to the 2nd party is stopped by the 1st party. In that case, after paying all the dues, the services should be taken regularly.
                </p>
                <p>
                    12. In case of using SMS, the 2nd party will purchase from the dashboard of the software in pre-paid form at the current fixed price i.e. 0.25 (twenty five paisa) per SMS. It should be noted that the price of SMS may increase if there are policy/price changes by the government and mobile companies.
                </p>
                <p>
                    13.  Any misuse of SMS / anti-state propaganda / use for political purposes etc. will be the responsibility of the 2nd party using the SMS.
                </p>
                <p>14. Apart from software, all hardware including printers, scanners, laptops, computers, routers, modems are not given/supplied by the 1st party, so the 1st party will not be obliged to provide any solution if there is a problem with them.</p>
                <p>
                    15. If the 2nd party fails to take the proper output of the software due to computer, laptop, printer, modem etc. or lack of focal point (manpower) etc., the bill or service charges received by the 1st party cannot be delayed or withheld in any way by showing any reason.
                </p>
                <p>
                    16. Before each examination the local representative of the 1st party will provide training to the teaching staff of the 2nd party regarding input of marks. The exam marks will be input by the 2nd party at their own risk. 2nd party will understand from 1st party representative whether all configuration is correct before mark input. Before finalizing the result, all corrections will be done by the 2nd party at their own responsibility.
                </p>
                <p>
                    17. In respect of Accounts module, accounting shall commence from the last closing balance / jr till the date of software handover, as per all accounts of 2nd party. The 1st party shall in no way undertake the responsibility of making previous voucher entries and reconciling previous accounts. In that case the 1st party shall provide necessary training to the 2nd party's nominee/persons. The 2nd party must change the password on their own responsibility to protect and preserve the confidential data of voucher entries and accounts.
                </p>
                <p>
                    18. All problems related to 2nd Party Software will be resolved by 1st Party online and tele-communication as soon as possible. If there is no solution through online or tele-communication, then the solution will be arranged in person. In that case the 2nd party may have to wait up to 3 working days.
                </p>
                <p>
                    19. If the 2nd party does not want to use the software during the term of the contract, then a written request must be made at least two months in advance by paying all dues.

                </p>

                <p>
                    In this situation, we both the parties having understood the meaning of all the information and conditions on both pages of the agreement, in sound mind, self-knowledge, without any inducement of others, sign this agreement today 05-11-2022 in the presence of the following witnesses.
                </p>
            </div>
           </div>
        </div>
    </div>
</body>
</html>