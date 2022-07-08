#!/bin/bash

#--------------------------------------------------------------------------------------------
# Jenkins build script
#--------------------------------------------------------------------------------------------

# Remove all *.gz files

echo "Begin removing old packages..."

rm -rf ${WORKSPACE}/*.gz
rm -rf ${WORKSPACE}/system/*.gz

echo "Removing an old package successfully."

echo "Current workspace directory: " ${WORKSPACE}

echo "Begin building the package"

# Build Website Package
FILE_NAME=deploy-build.tar.gz \
    ;
tar -zcf \
    $FILE_NAME \
    --exclude="*.git*" \
    --exclude="*.htaccess*" \
    --exclude="./wp-config**" \
    --exclude="./wp-content/uploads" \
    -C ${WORKSPACE}/app/public . \
    ;
unset FILE_NAME \
    ;
