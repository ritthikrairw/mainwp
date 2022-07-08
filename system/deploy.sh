#!/usr/bin/env bash

# Local .config file
if [ -f .config ]; then
    # Load Environment Variables
    export $(cat .config | grep -v '#' | awk '/=/ {print $1}')

    #--------------------------------------------------------------------------------------------
    # Server site
    #--------------------------------------------------------------------------------------------

    # Set variables
    USER_DIR=$__USER_DIR
    DIR_PATH=$__DIR_PATH
    PUBLIC_PATH=$DIR_PATH/public_html
    REVISION_NAME=$(date +"%Y%m%d%H%M%S")
    REVISION_PATH=$DIR_PATH/revisions/$REVISION_NAME
    SHARED_PATH=$DIR_PATH/shared

    # Check exist DIR

    echo "Check shared directory"
    [ ! -d "$SHARED_PATH" ] && sudo mkdir $SHARED_PATH

    echo "Check revisions directory"
    [ ! -d "$DIR_PATH/revisions" ] && sudo mkdir $DIR_PATH/revisions

    # Make revisions directory and remove files
    echo "Create $REVISION_PATH directory"
    sudo mkdir $REVISION_PATH

    echo "Copy deploy-build.tar.gz to $REVISION_PATH directory"
    sudo cp $USER_DIR/deploy/deploy-build.tar.gz $REVISION_PATH
    cd $REVISION_PATH

    echo "Extract deploy-build.tar.gz"
    sudo tar -xzf deploy-build.tar.gz

    echo "Remove deploy-build.tar.gz"
    sudo rm deploy-build.tar.gz

    # Check exist files
    FILE=$REVISION_PATH/.htaccess
    if [ -f "$FILE" ]; then
        sudo rm -rf $FILE
    fi

    FILE=$REVISION_PATH/wp-config.php
    if [ -f "$FILE" ]; then
        sudo rm -rf $FILE
    fi

    DIR=$REVISION_PATH/wp-content/uploads
    if [ -d "$DIR" ]; then
        sudo rm -rf $DIR
    fi

    # Symlinks
    sudo rm $PUBLIC_PATH
    sudo ln -s $REVISION_PATH $PUBLIC_PATH
    sudo ln -s $SHARED_PATH/uploads $PUBLIC_PATH/wp-content
    sudo ln -s $SHARED_PATH/.htaccess $PUBLIC_PATH/.htaccess
    sudo ln -s $SHARED_PATH/wp-config.php $PUBLIC_PATH/wp-config.php

    # Set chown and chmod
    USER=$__USER
    GROUP=$__GROUP

    sudo chown -R $USER:$GROUP $REVISION_PATH/
    sudo chown -R $USER:$GROUP $SHARED_PATH/
    sudo chown -R $USER:$GROUP $DIR_PATH/revisions/
    sudo chown -R $USER:$GROUP $PUBLIC_PATH/
    sudo chown -R $USER:$GROUP $PUBLIC_PATH
    sudo chmod 755 $REVISION_PATH/
    sudo chmod 755 $SHARED_PATH/
    sudo chmod 755 $DIR_PATH/revisions/
    sudo chmod 755 $PUBLIC_PATH/
    sudo chmod 444 $PUBLIC_PATH/.htaccess
    sudo chmod 444 $PUBLIC_PATH/nginx.conf
    sudo chmod 444 $PUBLIC_PATH/wp-config.php

    # Unset variables and remove files
    sudo rm -rf $USER_DIR/deploy
    unset DIR_PATH
    unset PUBLIC_PATH
    unset REVISION_NAME
    unset REVISION_PATH
    unset SHARED_PATH
    unset FILE
    unset DIR

    # Restart services
    sudo service php7.4-fpm restart
    sudo service php8.0-fpm restart
    sudo service nginx restart

fi
