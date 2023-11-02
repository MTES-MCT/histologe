#!/bin/bash

# Script Name: upload-s3.sh
#
# Description: This script is used to upload files to a cloud bucket.
#
# Prerequisites:
# - The BUCKET_URL environment variable must be set before running the script.
#   Example: export BUCKET_URL=my-bucket-name
#
# Usage: ./script.sh [option] [zip]
#   option - The type of file to upload (grid, signalement, or image)
#   zip - The code of the department
#
# Example 1: ./scripts/upload-s3.sh grid 33
# Example 2: ./scripts/upload-s3.sh signalement 33
# Example 3: ./scripts/upload-s3.sh image 33
# Example 4: ./scripts/upload-s3.sh mapping-doc 33
# Example 5: ./scripts/upload-s3.sh process-all 33
#
# Notice:
# - File must be executed in local, ignored during the deployment
#

if [ -z "$BUCKET_URL" ]; then
  echo "BUCKET_URL variable not set"
else
  echo "The value of BUCKET_URL is: $BUCKET_URL"
  option=$1
  zip=$2
  debug=${3:null}
  if [ -z "$zip" ]; then
    echo "zip argument is missing: ./scripts/upload-s3.sh [option] [zip]"
    exit 1
  fi
  case "$option" in
    "grid")
      echo "Upload grille_affectation_$2.csv to cloud..."
      aws s3 cp data/grid-affectation/grille_affectation_${zip}.csv s3://${BUCKET_URL}/csv/ ${debug}
      aws s3 ls s3://${BUCKET_URL}/csv/grille_affectation_${zip}.csv
      ;;
    "signalement")
      echo "Upload signalement_$2.csv to cloud..."
      aws s3 cp data/signalement/signalement_${zip}.csv s3://${BUCKET_URL}/csv/ ${debug}
      aws s3 ls s3://${BUCKET_URL}/csv/signalement_${zip}.csv
      ;;
    "slugify-signalement")
      echo "Upload signalement_$2.csv to cloud..."
      aws s3 cp data/images/signalement_${zip}.csv s3://${BUCKET_URL}/csv/ ${debug}
      aws s3 ls s3://${BUCKET_URL}/csv/signalement_${zip}.csv
      ;;
    "image")
      echo "Upload image_$zip to cloud"
      aws s3 cp --recursive data/images/import_${zip} s3://${BUCKET_URL}/ ${debug}
      ;;
    "mapping-doc")
      echo "Upload mapping_doc_signalement_$zip to cloud"
      aws s3 cp data/images/mapping_doc_signalement_${zip}.csv s3://${BUCKET_URL}/csv/ ${debug}
      aws s3 ls s3://${BUCKET_URL}/csv/mapping_doc_signalement_${zip}.csv
      ;;
    "process-all")
      echo "Upload mapping_doc_signalement_$zip to cloud"
      aws s3 cp data/images/mapping_doc_signalement_${zip}.csv s3://${BUCKET_URL}/csv/ ${debug}
      aws s3 ls s3://${BUCKET_URL}/csv/mapping_doc_signalement_${zip}.csv
      make console app="slugify-doc-signalement ${zip}"
      result=$?
      if [ $result -eq 0 ]; then
        echo "Upload image_$zip to cloud"
        aws s3 cp --recursive data/images/import_${zip} s3://${BUCKET_URL}/ ${debug}
        make console app="update-doc-signalement ${zip}"
      else
          echo "Please check the message"
      fi
      ;;
    *)
      echo "Invalid argument. Please use 'grid' or 'signalement' or 'image' or 'mapping-doc' or 'process-all'"
      ;;
  esac
fi
