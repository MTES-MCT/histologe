<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241023122343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'assign 2 Bordeaux partners when main partner is already assigned';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(true, 'No need to execute this migration in localhost or review app');
        $idPartner7130 = 7130;
        $idPartner7131 = 7131;

        $listToAssignTo7130 = [
            '2024-1364',
            '2024-1363',
            '2024-1360',
            '2024-1359',
            '2024-1358',
            '2024-1351',
            '2024-1350',
            '2024-1348',
            '2024-1341',
            '2024-1337',
            '2024-1333',
            '2024-1329',
            '2024-1328',
            '2024-1325',
            '2024-1319',
            '2024-1318',
            '2024-1317',
            '2024-1316',
            '2024-1315',
            '2024-1314',
            '2024-1313',
            '2024-1311',
            '2024-1310',
            '2024-1309',
            '2024-1308',
            '2024-1307',
            '2024-1306',
            '2024-1301',
            '2024-1296',
            '2024-1290',
            '2024-1284',
            '2024-1281',
            '2024-1280',
            '2024-1278',
            '2024-1277',
            '2024-1275',
            '2024-1273',
            '2024-1265',
            '2024-1263',
            '2024-1261',
            '2024-1258',
            '2024-1256',
            '2024-1254',
            '2024-1248',
            '2024-1245',
            '2024-1240',
            '2024-1239',
            '2024-1238',
            '2024-1233',
            '2024-1232',
            '2024-1226',
            '2024-1225',
            '2024-1224',
            '2024-1216',
            '2024-1215',
            '2024-1212',
            '2024-1211',
            '2024-1208',
            '2024-1207',
            '2024-1203',
            '2024-1202',
            '2024-1201',
            '2024-1196',
            '2024-1195',
            '2024-1192',
            '2024-1190',
            '2024-1187',
            '2024-1186',
            '2024-1182',
            '2024-1181',
            '2024-1180',
            '2024-1175',
            '2024-1173',
            '2024-1169',
            '2024-1167',
            '2024-1162',
            '2024-1156',
            '2024-1154',
            '2024-1146',
            '2024-1144',
            '2024-1143',
            '2024-1140',
            '2024-1139',
            '2024-1138',
            '2024-1136',
            '2024-1135',
            '2024-1133',
            '2024-1132',
            '2024-1130',
            '2024-1128',
            '2024-1120',
            '2024-1119',
            '2024-1118',
            '2024-1115',
            '2024-1114',
            '2024-1113',
            '2024-1110',
            '2024-1101',
            '2024-1100',
            '2024-1097',
            '2024-1095',
            '2024-1093',
            '2024-1089',
            '2024-1085',
            '2024-1082',
            '2024-1080',
            '2024-1077',
            '2024-1073',
            '2024-1072',
            '2024-1070',
            '2024-1069',
            '2024-1061',
            '2024-1060',
            '2024-1055',
            '2024-1054',
            '2024-1042',
            '2024-1041',
            '2024-1040',
            '2024-1039',
            '2024-1034',
            '2024-1028',
            '2024-1026',
            '2024-1023',
            '2024-1013',
            '2024-1004',
            '2024-1000',
            '2024-995',
            '2024-992',
            '2024-989',
            '2024-987',
            '2024-986',
            '2024-981',
            '2024-978',
            '2024-964',
            '2024-963',
            '2024-962',
            '2024-960',
            '2024-958',
            '2024-956',
            '2024-952',
            '2024-949',
            '2024-948',
            '2024-946',
            '2024-945',
            '2024-941',
            '2024-940',
            '2024-939',
            '2024-937',
            '2024-935',
            '2024-934',
            '2024-933',
            '2024-931',
            '2024-924',
            '2024-923',
            '2024-920',
            '2024-916',
            '2024-910',
            '2024-907',
            '2024-905',
            '2024-900',
            '2024-898',
            '2024-895',
            '2024-892',
            '2024-890',
            '2024-887',
            '2024-882',
            '2024-877',
            '2024-874',
            '2024-871',
            '2024-869',
            '2024-867',
            '2024-858',
            '2024-854',
            '2024-847',
            '2024-844',
            '2024-837',
            '2024-836',
            '2024-833',
            '2024-832',
            '2024-828',
            '2024-827',
            '2024-825',
            '2024-822',
            '2024-820',
            '2024-819',
            '2024-809',
            '2024-808',
            '2024-807',
            '2024-805',
            '2024-803',
            '2024-802',
            '2024-796',
            '2024-792',
            '2024-790',
            '2024-789',
            '2024-788',
            '2024-786',
            '2024-784',
            '2024-783',
            '2024-778',
            '2024-776',
            '2024-765',
            '2024-758',
            '2024-757',
            '2024-755',
            '2024-752',
            '2024-749',
            '2024-747',
            '2024-745',
            '2024-738',
            '2024-731',
            '2024-728',
            '2024-726',
            '2024-725',
            '2024-724',
            '2024-716',
            '2024-700',
            '2024-695',
            '2024-693',
            '2024-683',
            '2024-682',
            '2024-681',
            '2024-678',
            '2024-677',
            '2024-670',
            '2024-668',
            '2024-665',
            '2024-660',
            '2024-651',
            '2024-640',
            '2024-632',
            '2024-629',
            '2024-626',
            '2024-624',
            '2024-619',
            '2024-613',
            '2024-610',
            '2024-608',
            '2024-607',
            '2024-606',
            '2024-603',
            '2024-600',
            '2024-596',
            '2024-594',
            '2024-593',
            '2024-591',
            '2024-590',
            '2024-585',
            '2024-584',
            '2024-579',
            '2024-573',
            '2024-570',
            '2024-569',
            '2024-567',
            '2024-566',
            '2024-562',
            '2024-561',
            '2024-556',
            '2024-549',
            '2024-548',
            '2024-530',
            '2024-523',
            '2024-522',
            '2024-521',
            '2024-517',
            '2024-514',
            '2024-513',
            '2024-512',
            '2024-511',
            '2024-510',
            '2024-502',
            '2024-493',
            '2024-492',
            '2024-488',
            '2024-486',
            '2024-479',
            '2024-476',
            '2024-475',
            '2024-474',
            '2024-473',
            '2024-471',
            '2024-470',
            '2024-469',
            '2024-467',
            '2024-461',
            '2024-456',
            '2024-454',
            '2024-451',
            '2024-446',
            '2024-439',
            '2024-434',
            '2024-427',
            '2024-426',
            '2024-425',
            '2024-424',
            '2024-423',
            '2024-419',
            '2024-414',
            '2024-410',
            '2024-408',
            '2024-407',
            '2024-405',
            '2024-403',
            '2024-401',
            '2024-400',
            '2024-398',
            '2024-397',
            '2024-394',
            '2024-393',
            '2024-392',
            '2024-391',
            '2024-388',
            '2024-387',
            '2024-384',
            '2024-378',
            '2024-372',
            '2024-370',
            '2024-365',
            '2024-359',
            '2024-358',
            '2024-356',
            '2024-355',
            '2024-346',
            '2024-345',
            '2024-343',
            '2024-338',
            '2024-337',
            '2024-335',
            '2024-334',
            '2024-332',
            '2024-331',
            '2024-317',
            '2024-311',
            '2024-310',
            '2024-309',
            '2024-308',
            '2024-306',
            '2024-304',
            '2024-299',
            '2024-297',
            '2024-291',
            '2024-290',
            '2024-289',
            '2024-287',
            '2024-283',
            '2024-282',
            '2024-281',
            '2024-280',
            '2024-275',
            '2024-274',
            '2024-269',
            '2024-268',
            '2024-264',
            '2024-260',
            '2024-257',
            '2024-255',
            '2024-252',
            '2024-246',
            '2024-245',
            '2024-242',
            '2024-240',
            '2024-238',
            '2024-237',
            '2024-232',
            '2024-229',
            '2024-226',
            '2024-223',
            '2024-222',
            '2024-221',
            '2024-214',
            '2024-208',
            '2024-207',
            '2024-199',
            '2024-197',
            '2024-192',
            '2024-186',
            '2024-182',
            '2024-181',
            '2024-180',
            '2024-178',
            '2024-176',
            '2024-175',
            '2024-169',
            '2024-164',
            '2024-159',
            '2024-156',
            '2024-153',
            '2024-152',
            '2024-151',
            '2024-147',
            '2024-145',
            '2024-144',
            '2024-143',
            '2024-142',
            '2024-134',
            '2024-123',
            '2024-122',
            '2024-120',
            '2024-119',
            '2024-118',
            '2024-115',
            '2024-113',
            '2024-112',
            '2024-110',
            '2024-109',
            '2024-104',
            '2024-100',
            '2024-99',
            '2024-96',
            '2024-94',
            '2024-93',
            '2024-92',
            '2024-90',
            '2024-88',
            '2024-87',
            '2024-85',
            '2024-84',
            '2024-83',
            '2024-81',
            '2024-78',
            '2024-76',
            '2024-74',
            '2024-72',
            '2024-70',
            '2024-68',
            '2024-66',
            '2024-52',
            '2024-49',
            '2024-43',
            '2024-38',
            '2024-35',
            '2024-34',
            '2024-30',
            '2024-29',
            '2024-28',
            '2024-27',
            '2024-26',
            '2024-24',
            '2024-23',
            '2024-19',
            '2024-16',
            '2024-15',
            '2024-14',
            '2024-6',
            '2024-5',
            '2024-2',
            '2023-1210',
            '2023-1203',
            '2023-1202',
            '2023-1197',
            '2023-1196',
            '2023-1194',
            '2023-1193',
            '2023-1191',
            '2023-1189',
            '2023-1187',
            '2023-1184',
            '2023-1183',
            '2023-1179',
            '2023-1176',
            '2023-1175',
            '2023-1173',
            '2023-1171',
            '2023-1170',
            '2023-1166',
            '2023-1164',
            '2023-1158',
            '2023-1157',
            '2023-1151',
            '2023-1144',
            '2023-1132',
            '2023-1131',
            '2023-1130',
            '2023-1129',
            '2023-1125',
            '2023-1118',
            '2023-1116',
            '2023-1110',
            '2023-1104',
            '2023-1103',
            '2023-1102',
            '2023-1096',
            '2023-1094',
            '2023-1093',
            '2023-1087',
            '2023-1083',
            '2023-1082',
            '2023-1080',
            '2023-1076',
            '2023-1074',
            '2023-1072',
            '2023-1071',
            '2023-1070',
            '2023-1065',
            '2023-1064',
            '2023-1057',
            '2023-1042',
            '2023-1037',
            '2023-1036',
            '2023-1035',
            '2023-1033',
            '2023-1028',
            '2023-1027',
            '2023-1018',
            '2023-1011',
            '2023-1010',
            '2023-1007',
            '2023-1005',
            '2023-1003',
            '2023-1001',
            '2023-998',
            '2023-997',
            '2023-993',
            '2023-985',
            '2023-982',
            '2023-980',
            '2023-979',
            '2023-975',
            '2023-974',
            '2023-973',
            '2023-970',
            '2023-968',
            '2023-962',
            '2023-961',
            '2023-960',
            '2023-958',
            '2023-953',
            '2023-952',
            '2023-950',
            '2023-949',
            '2023-947',
            '2023-946',
            '2023-942',
            '2023-940',
            '2023-939',
            '2023-933',
            '2023-932',
            '2023-931',
            '2023-927',
            '2023-924',
            '2023-921',
            '2023-917',
            '2023-916',
            '2023-911',
            '2023-905',
            '2023-901',
            '2023-898',
            '2023-896',
            '2023-895',
            '2023-894',
            '2023-893',
            '2023-891',
            '2023-890',
            '2023-887',
            '2023-881',
            '2023-880',
            '2023-879',
            '2023-875',
            '2023-872',
            '2023-870',
            '2023-868',
            '2023-867',
            '2023-866',
            '2023-865',
            '2023-862',
            '2023-857',
            '2023-856',
            '2023-853',
            '2023-841',
            '2023-840',
            '2023-836',
            '2023-832',
            '2023-830',
            '2023-829',
            '2023-828',
            '2023-814',
            '2023-807',
            '2023-799',
            '2023-798',
            '2023-795',
            '2023-794',
            '2023-792',
            '2023-789',
            '2023-787',
            '2023-778',
            '2023-775',
            '2023-767',
            '2023-763',
            '2023-761',
            '2023-757',
            '2023-756',
            '2023-754',
            '2023-753',
            '2023-751',
            '2023-750',
            '2023-746',
            '2023-740',
            '2023-737',
            '2023-735',
            '2023-733',
            '2023-732',
            '2023-731',
            '2023-730',
            '2023-728',
            '2023-727',
            '2023-726',
            '2023-724',
            '2023-722',
            '2023-721',
            '2023-718',
            '2023-714',
            '2023-713',
            '2023-710',
            '2023-709',
            '2023-704',
            '2023-701',
            '2023-700',
            '2023-698',
            '2023-697',
            '2023-692',
            '2023-691',
            '2023-687',
            '2023-686',
            '2023-682',
            '2023-678',
            '2023-677',
            '2023-667',
            '2023-660',
            '2023-656',
            '2023-651',
            '2023-647',
            '2023-646',
            '2023-645',
            '2023-642',
            '2023-637',
            '2023-632',
            '2023-631',
            '2023-624',
            '2023-619',
            '2023-618',
            '2023-614',
            '2023-612',
            '2023-611',
            '2023-610',
            '2023-608',
            '2023-606',
            '2023-604',
            '2023-602',
            '2023-587',
            '2023-581',
            '2023-580',
            '2023-578',
            '2023-576',
            '2023-575',
            '2023-574',
            '2023-573',
            '2023-570',
            '2023-564',
            '2023-553',
            '2023-552',
            '2023-549',
            '2023-545',
            '2023-534',
            '2023-533',
            '2023-532',
            '2023-530',
            '2023-529',
            '2023-528',
            '2023-516',
            '2023-514',
            '2023-513',
            '2023-509',
            '2023-508',
            '2023-501',
            '2023-498',
            '2023-496',
            '2023-493',
            '2023-492',
            '2023-483',
            '2023-482',
            '2023-479',
            '2023-477',
            '2023-474',
            '2023-472',
            '2023-471',
            '2023-470',
            '2023-467',
            '2023-464',
            '2023-463',
            '2023-458',
            '2023-456',
            '2023-455',
            '2023-454',
            '2023-451',
            '2023-445',
            '2023-443',
            '2023-441',
            '2023-439',
            '2023-433',
            '2023-432',
            '2023-429',
            '2023-427',
            '2023-420',
            '2023-419',
            '2023-417',
            '2023-416',
            '2023-414',
            '2023-409',
            '2023-406',
            '2023-405',
            '2023-401',
            '2023-389',
            '2023-388',
            '2023-384',
            '2023-383',
            '2023-382',
            '2023-381',
            '2023-377',
            '2023-376',
            '2023-375',
            '2023-373',
            '2023-372',
            '2023-370',
            '2023-368',
            '2023-365',
            '2023-364',
            '2023-352',
            '2023-346',
            '2023-344',
            '2023-341',
            '2023-338',
            '2023-337',
            '2023-336',
            '2023-335',
            '2023-330',
            '2023-326',
            '2023-325',
            '2023-323',
            '2023-320',
            '2023-319',
            '2023-318',
            '2023-317',
            '2023-313',
            '2023-311',
            '2023-294',
            '2023-289',
            '2023-287',
            '2023-285',
            '2023-283',
            '2023-282',
            '2023-280',
            '2023-276',
            '2023-274',
            '2023-271',
            '2023-267',
            '2023-264',
            '2023-259',
            '2023-258',
            '2023-257',
            '2023-252',
            '2023-250',
            '2023-244',
            '2023-243',
            '2023-242',
            '2023-239',
            '2023-238',
            '2023-233',
            '2023-232',
            '2023-230',
            '2023-229',
            '2023-223',
            '2023-222',
            '2023-219',
            '2023-216',
            '2023-211',
            '2023-209',
            '2023-208',
            '2023-207',
            '2023-206',
            '2023-194',
            '2023-193',
            '2023-192',
            '2023-188',
            '2023-186',
            '2023-185',
            '2023-180',
            '2023-175',
            '2023-165',
            '2023-164',
            '2023-163',
            '2023-157',
            '2023-154',
            '2023-153',
            '2023-151',
            '2023-150',
            '2023-149',
            '2023-146',
            '2023-143',
            '2023-135',
            '2023-134',
            '2023-128',
            '2023-127',
            '2023-126',
            '2023-124',
            '2023-122',
            '2023-121',
            '2023-120',
            '2023-119',
            '2023-117',
            '2023-115',
            '2023-112',
            '2023-111',
            '2023-109',
            '2023-105',
            '2023-103',
            '2023-102',
            '2023-100',
            '2023-98',
            '2023-89',
            '2023-86',
            '2023-85',
            '2023-83',
            '2023-78',
            '2023-72',
            '2023-70',
            '2023-66',
            '2023-64',
            '2023-63',
            '2023-61',
            '2023-60',
            '2023-56',
            '2023-55',
            '2023-52',
            '2023-50',
            '2023-48',
            '2023-38',
            '2023-33',
            '2023-30',
            '2023-23',
            '2023-21',
            '2023-15',
        ];

        $listToAssignTo7131 = [
            '2023-661',
            '2024-32',
            '2024-1131',
            '2024-746',
            '2024-216',
            '2024-1357',
            '2024-1339',
            '2024-1223',
            '2024-1137',
            '2024-1134',
            '2024-1102',
            '2024-1078',
            '2024-1027',
            '2024-922',
            '2024-911',
            '2024-781',
            '2024-644',
            '2024-583',
            '2024-560',
            '2024-500',
            '2024-273',
            '2024-163',
            '2024-127',
            '2023-1121',
            '2023-1105',
            '2023-903',
            '2023-839',
            '2023-719',
            '2023-517',
            '2023-332',
            '2023-310',
            '2023-309',
            '2023-308',
            '2023-307',
            '2023-305',
            '2023-304',
            '2023-303',
            '2023-302',
            '2023-301',
            '2023-300',
            '2023-299',
            '2023-297',
            '2023-296',
            '2023-295',
            '2023-268',
            '2023-217',
            '2023-137',
            '2023-140',
            '2024-1322',
            '2024-1210',
            '2024-1063',
            '2024-984',
            '2024-974',
            '2024-759',
            '2024-557',
            '2024-550',
            '2024-146',
            '2023-835',
            '2023-594',
            '2023-491',
            '2023-444',
            '2023-358',
            '2023-187',
            '2024-1335',
            '2024-1151',
            '2024-1149',
            '2024-1129',
            '2024-1043',
            '2024-930',
            '2024-741',
            '2024-659',
            '2024-508',
            '2024-491',
            '2024-484',
            '2024-441',
            '2024-418',
            '2024-320',
            '2024-316',
            '2024-243',
            '2024-236',
            '2024-219',
            '2024-205',
            '2024-191',
            '2024-161',
            '2024-75',
            '2024-3',
            '2024-1',
            '2023-1182',
            '2023-1165',
            '2023-1085',
            '2023-1053',
            '2023-959',
            '2023-912',
            '2023-873',
            '2023-860',
            '2023-782',
            '2023-765',
            '2023-715',
            '2023-675',
            '2023-665',
            '2023-605',
            '2023-465',
            '2023-404',
            '2023-387',
            '2023-265',
            '2023-218',
            '2023-205',
            '2023-141',
            '2023-129',
            '2023-10',
            '2024-1158',
            '2024-902',
            '2024-673',
            '2024-239',
            '2023-1038',
            '2023-524',
            '2024-1321',
            '2024-1200',
            '2024-1172',
            '2024-1164',
            '2024-1153',
            '2024-1124',
            '2024-1092',
            '2024-1059',
            '2024-1051',
            '2024-999',
            '2024-957',
            '2024-942',
            '2024-917',
            '2024-823',
            '2024-800',
            '2024-767',
            '2024-751',
            '2024-750',
            '2024-719',
            '2024-703',
            '2024-671',
            '2024-597',
            '2024-545',
            '2024-381',
            '2024-56',
            '2023-1019',
            '2023-1009',
            '2023-999',
            '2023-885',
            '2023-685',
            '2023-659',
            '2023-367',
            '2023-324',
            '2023-251',
            '2023-8',
            '2024-1127',
            '2024-909',
            '2024-908',
            '2024-793',
            '2024-785',
            '2024-769',
            '2024-766',
            '2024-713',
            '2024-708',
            '2024-564',
            '2024-465',
            '2024-250',
            '2024-227',
            '2023-1198',
            '2023-1032',
            '2023-1023',
            '2023-854',
            '2023-846',
            '2023-707',
            '2023-473',
            '2024-1289',
            '2024-1288',
            '2024-1279',
            '2024-1269',
            '2024-1267',
            '2024-1262',
            '2024-1257',
            '2024-1247',
            '2024-1230',
            '2024-1157',
            '2024-1147',
            '2024-1122',
            '2024-1117',
            '2024-1107',
            '2024-1104',
            '2024-1084',
            '2024-1066',
            '2024-1049',
            '2024-928',
            '2024-927',
            '2024-903',
            '2024-891',
            '2024-883',
            '2024-876',
            '2024-860',
            '2024-843',
            '2024-729',
            '2024-720',
            '2024-715',
            '2024-679',
            '2024-653',
            '2024-652',
            '2024-643',
            '2024-635',
            '2024-499',
            '2024-285',
            '2024-254',
            '2024-233',
            '2024-215',
            '2024-212',
            '2024-209',
            '2024-206',
            '2024-185',
            '2024-138',
            '2024-33',
            '2023-1195',
            '2023-1113',
            '2023-967',
            '2023-922',
            '2023-920',
            '2023-889',
            '2023-811',
            '2023-462',
            '2023-424',
            '2023-386',
            '2023-262',
            '2023-227',
            '2023-203',
            '2023-118',
            '2023-116',
            '2023-101',
            '2023-1200',
            '2023-711',
            '2024-1352',
            '2024-1345',
            '2024-1300',
            '2024-1298',
            '2024-1282',
            '2024-1178',
            '2024-1176',
            '2024-1145',
            '2024-1142',
            '2024-1109',
            '2024-1091',
            '2024-1021',
            '2024-1018',
            '2024-944',
            '2024-906',
            '2024-797',
            '2024-676',
            '2024-667',
            '2024-587',
            '2024-586',
            '2024-531',
            '2024-524',
            '2024-436',
            '2024-321',
            '2024-303',
            '2024-293',
            '2024-263',
            '2024-230',
            '2024-225',
            '2024-188',
            '2024-141',
            '2024-121',
            '2024-101',
            '2024-40',
            '2023-1160',
            '2023-1122',
            '2023-1050',
            '2023-1020',
            '2023-935',
            '2023-810',
            '2023-783',
            '2023-762',
            '2023-743',
            '2023-729',
            '2023-663',
            '2023-639',
            '2023-620',
            '2023-469',
            '2023-450',
            '2023-362',
            '2023-249',
            '2023-162',
            '2023-45',
            '2023-926',
            '2024-938',
            '2024-742',
            '2024-666',
            '2024-628',
            '2024-406',
            '2024-279',
            '2024-131',
            '2024-47',
            '2023-1199',
            '2023-978',
            '2023-944',
            '2023-914',
            '2023-459',
            '2024-1086',
            '2024-1033',
            '2023-1029',
            '2023-948',
            '2023-447',
            '2024-1053',
            '2024-982',
            '2024-509',
            '2024-235',
            '2023-536',
            '2023-468',
            '2023-123',
            '2023-27',
            '2024-1356',
            '2024-1331',
            '2024-1276',
            '2024-1272',
            '2024-1174',
            '2024-1163',
            '2024-1079',
            '2024-1075',
            '2024-1074',
            '2024-1048',
            '2024-955',
            '2024-904',
            '2024-838',
            '2024-834',
            '2024-761',
            '2024-498',
            '2024-472',
            '2024-468',
            '2024-432',
            '2024-350',
            '2024-327',
            '2024-324',
            '2024-267',
            '2024-253',
            '2024-201',
            '2024-193',
            '2024-8',
            '2023-1190',
            '2023-1134',
            '2023-1095',
            '2023-1006',
            '2023-988',
            '2023-984',
            '2023-977',
            '2023-934',
            '2023-929',
            '2023-813',
            '2023-802',
            '2023-681',
            '2023-655',
            '2023-600',
            '2023-582',
            '2023-395',
            '2023-353',
            '2023-334',
            '2023-148',
            '2023-138',
            '2023-9',
            '2023-555',
            '2023-215',
            '2024-1362',
            '2024-1330',
            '2024-1299',
            '2024-1243',
            '2024-1237',
            '2024-1206',
            '2024-1193',
            '2024-1160',
            '2024-1155',
            '2024-1111',
            '2024-1105',
            '2024-1103',
            '2024-1029',
            '2024-1016',
            '2024-1011',
            '2024-1007',
            '2024-998',
            '2024-994',
            '2024-990',
            '2024-971',
            '2024-899',
            '2024-880',
            '2024-865',
            '2024-863',
            '2024-862',
            '2024-855',
            '2024-831',
            '2024-816',
            '2024-801',
            '2024-712',
            '2024-701',
            '2024-691',
            '2024-680',
            '2024-674',
            '2024-636',
            '2024-571',
            '2024-558',
            '2024-520',
            '2024-507',
            '2024-483',
            '2024-455',
            '2024-430',
            '2024-353',
            '2024-305',
            '2024-272',
            '2024-220',
            '2024-204',
            '2024-196',
            '2024-150',
            '2024-130',
            '2024-106',
            '2024-31',
            '2024-17',
            '2024-13',
            '2023-1186',
            '2023-1178',
            '2023-1168',
            '2023-1069',
            '2023-1063',
            '2023-1049',
            '2023-1021',
            '2023-1016',
            '2023-1015',
            '2023-965',
            '2023-954',
            '2023-915',
            '2023-876',
            '2023-852',
            '2023-831',
            '2023-808',
            '2023-805',
            '2023-776',
            '2023-734',
            '2023-689',
            '2023-683',
            '2023-671',
            '2023-643',
            '2023-640',
            '2023-633',
            '2023-601',
            '2023-489',
            '2023-481',
            '2023-391',
            '2023-354',
            '2023-327',
            '2023-315',
            '2023-248',
            '2023-247',
            '2023-240',
            '2023-181',
            '2023-107',
            '2023-91',
            '2023-80',
            '2023-79',
            '2023-67',
            '2023-40',
            '2023-37',
            '2023-899',
            '2023-749',
            '2023-684',
            '2023-556',
            '2024-187',
            '2024-53',
            '2023-485',
            '2024-67',
            '2023-275',
            '2024-1343',
            '2024-1338',
            '2024-1292',
            '2024-1229',
            '2024-1220',
            '2024-1205',
            '2024-1185',
            '2024-1123',
            '2024-1081',
            '2024-1076',
            '2024-1045',
            '2024-1024',
            '2024-968',
            '2024-951',
            '2024-950',
            '2024-919',
            '2024-884',
            '2024-853',
            '2024-812',
            '2024-732',
            '2024-692',
            '2024-687',
            '2024-642',
            '2024-577',
            '2024-552',
            '2024-452',
            '2024-409',
            '2024-385',
            '2024-383',
            '2024-172',
            '2024-160',
            '2024-140',
            '2024-135',
            '2024-133',
            '2024-111',
            '2024-25',
            '2023-1058',
            '2023-910',
            '2023-907',
            '2023-815',
            '2023-708',
            '2023-673',
            '2023-453',
            '2023-397',
            '2023-393',
            '2023-360',
            '2023-198',
            '2023-159',
            '2023-158',
            '2023-106',
            '2024-82',
            '2024-55',
            '2024-1354',
            '2024-1293',
            '2024-1019',
            '2024-914',
            '2024-669',
            '2024-539',
            '2024-369',
            '2024-357',
            '2024-348',
            '2024-333',
            '2024-307',
            '2024-157',
            '2024-154',
            '2024-41',
            '2024-37',
            '2023-1172',
            '2023-1022',
            '2023-1000',
            '2023-768',
            '2023-658',
            '2023-621',
            '2023-577',
            '2023-523',
            '2023-413',
            '2023-266',
            '2023-213',
        ];

        $this->insertNewAffectations($idPartner7130, $listToAssignTo7130);
        $this->insertNewAffectations($idPartner7131, $listToAssignTo7131);
    }

    private function insertNewAffectations($idNewPartner, $listToAssign): void
    {
        $territoryId = 34;
        $idOriginalPartner = 1760;

        foreach ($listToAssign as $reference) {
            $signalementId = $this->connection->fetchOne(
                'SELECT id FROM signalement WHERE reference = :reference AND territory_id = :territory_id',
                ['reference' => $reference, 'territory_id' => $territoryId]
            );
            $existingAffectation = $this->connection->fetchAssociative(
                'SELECT * FROM affectation WHERE signalement_id = :signalement_id AND partner_id = :partner_id AND territory_id = :territory_id',
                ['signalement_id' => $signalementId, 'partner_id' => $idOriginalPartner, 'territory_id' => $territoryId]
            );
            if (empty($existingAffectation)) {
                continue;
            }
            $this->addSql(
                'INSERT INTO affectation (signalement_id, partner_id, answered_by_id, affected_by_id, territory_id, answered_at, created_at, statut, motif_cloture, is_synchronized, motif_refus)
                VALUES (:signalement_id, :partner_id, :answered_by_id, :affected_by_id, :territory_id, :answered_at, :created_at, :statut, :motif_cloture, :is_synchronized, :motif_refus)',
                [
                    'signalement_id' => $signalementId,
                    'partner_id' => $idNewPartner,
                    'answered_by_id' => $existingAffectation['answered_by_id'],
                    'affected_by_id' => $existingAffectation['affected_by_id'],
                    'territory_id' => $existingAffectation['territory_id'],
                    'answered_at' => $existingAffectation['answered_at'],
                    'created_at' => $existingAffectation['created_at'],
                    'statut' => $existingAffectation['statut'],
                    'motif_cloture' => $existingAffectation['motif_cloture'],
                    'is_synchronized' => $existingAffectation['is_synchronized'],
                    'motif_refus' => $existingAffectation['motif_refus'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
