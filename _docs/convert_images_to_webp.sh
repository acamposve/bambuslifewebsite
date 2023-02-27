#!/bin/bash
cd ../assets/images/
IFS=$'\n'
for file in $(find . -type f  \( -name "*.jpg" -o -name "*.png" -o -name "*.jpeg" \));
do
  fileName="${file%.*}"
  cwebp "$file" -o "$fileName.webp"
  echo
done
cd ../../_docs/
