docker build --target devenv -t antonchristensen/clipboard:devenv .
docker run -p 8000:80 --rm -it -v $(pwd)/src:/var/www/html/ antonchristensen/clipboard:devenv
