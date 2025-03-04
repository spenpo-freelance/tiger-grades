#! /bin/bash

# check for the -b flag
if [[ "$*" == *"-b"* ]]; then
  echo "Syncing backwards..."
  rsync -avz ../wp-env/wordpress/wp-content/plugins/tiger-grades/ src
  return
fi

rsync -avz --delete src/* ../wp-env/wordpress/wp-content/plugins/tiger-grades 