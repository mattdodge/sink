sink
====

Often times, an organization will have multiple directories on a web server that need to be synced to individual GitHub repositories. Ideally, these directories would support some sort of Continuous Deployment as well. Toss in different branches with something like [git flow](http://nvie.com/posts/a-successful-git-branching-model/) and maintaining webhooks can get unruly pretty fast.

This repository aims to provide a one-stop-shop for all of your GitHub webhooks, relying on server-side config to keep different directories synced up with different branches on different repositories. With the [recent addition of organization-wide webhooks](https://github.com/blog/1933-introducing-organization-webhooks), administrators will ideally only need one webhook to keep all of their directories in "sink".

## Basic Usage

1. In some accessible directory on your web server, clone down sink. For example:

    ```sh
    cd /var/www
    git clone git@github.com:mattdodge/sink.git
    ```

2. Copy `config.ini.example` to `config.ini` and modify its contents to match your desired implementation. See below for more documentation on configuration options.

    **Be sure to change your SECRET_PHRASE, you will need it in the next step.**

3. From your GitHub organization or from an individual repo, add a webhook to point to your recently cloned sync directory. Following up on our previous example:
    - **Payload URL**: *http://yourhost.com/sink*
    - **Content-Type**: *application/json*
    - **Secret**: *The secret phrase you configured in step 2*
  
