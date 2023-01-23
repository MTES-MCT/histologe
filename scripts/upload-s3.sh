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
# Example: ./scripts/upload-s3.sh grid 33
#

if [ -z "$BUCKET_URL" ]; then
  echo "BUCKET_URL variable not set"
else
  echo "The value of BUCKET_URL is: $BUCKET_URL"
  option=$1
  zip=$2

  case "$option" in
    "grid")
      echo "Upload grille_affectation_$2.csv to cloud..."
      aws s3 cp data/grid-affectation/grille_affectation_${zip}.csv s3://${BUCKET_URL}/csv
      ;;
    "signalement")
      echo "Upload signalement_$2.csv to cloud..."
      aws s3 cp data/signalement/signalement_${zip}.csv s3://${BUCKET_URL}/csv
      ;;
    "image")
      echo "Upload image_$zip to cloud"
      aws s3 cp --recursive data/images/import_${zip} s3://${BUCKET_URL}/
      ;;
    *)
      echo "Invalid argument. Please use 'grid' or 'signalement' or 'image'"
      ;;
  esac
fi
