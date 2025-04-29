#! /bin/bash
TIGER_GRADES_PATH=$HOME/Desktop/dev/tiger-grades
WORDPRESS_PATH=$HOME/Desktop/dev/wp-env/wordpress

# check for the -b flag
if [[ "$*" == *"-b"* ]]; then
  echo "Syncing backwards..."
  rsync -avz $WORDPRESS_PATH/wp-content/plugins/tiger-grades/ $TIGER_GRADES_PATH/src
  return
fi

rsync -avz --delete $TIGER_GRADES_PATH/src/* $WORDPRESS_PATH/wp-content/plugins/tiger-grades 
cp $TIGER_GRADES_PATH/src/.env $WORDPRESS_PATH/wp-content/plugins/tiger-grades/.env
