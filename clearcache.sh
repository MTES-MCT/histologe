#!/bash
cd ./AM && echo 'AM' && composer update && sudo php bin/console cache:clear &&
cd ../AHP && echo 'AHP'  && composer update && sudo php bin/console cache:clear &&
cd ../BDR && echo 'BDR'  && composer update && sudo php bin/console cache:clear &&
cd ../DEMO && echo 'DEMO'  && composer update && sudo php bin/console cache:clear &&
cd ../HG && echo 'HG'  && composer update && sudo php bin/console cache:clear &&
cd ../MEL && echo 'MEL'  && composer update && sudo php bin/console cache:clear &&
cd ../PAU && echo 'PAU'  && composer update && sudo php bin/console cache:clear &&
cd ../S\&L && echo 'SEL'  && composer update && sudo php bin/console cache:clear &&
cd ../VDG && echo 'VDG'  && composer update && sudo php bin/console cache:clear &&
cd ../CRZ && echo 'CRZ'  && composer update && sudo php bin/console cache:clear &&
cd ../ARD && echo 'ARD'  && composer update && sudo php bin/console cache:clear &&
cd ../CDS && echo 'CDS'  && composer update && sudo php bin/console cache:clear &&
cd ../RHO && echo 'RHO'  && composer update && sudo php bin/console cache:clear &&
cd ../PUY && echo 'PUY'  && composer update && sudo php bin/console cache:clear &&
cd ../TARN && echo 'TARN'  && composer update && sudo php bin/console cache:clear &&
echo "Composer updated !"

