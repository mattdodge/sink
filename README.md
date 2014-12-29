sink
====

Often times, an organization will have multiple directories on a web server that need to be synced to individual GitHub repositories. Ideally, these directories would support some sort of Continuous Deployment as well. Toss in different branches with something like [git flow](http://nvie.com/posts/a-successful-git-branching-model/) and maintaining webhooks can get unruly pretty fast.

This repository aims to provide a one-stop-shop for all of your GitHub webhooks, relying on server-side config to keep different directories synced up with different branches on different repositories. With the [recent addition of organization-wide webhooks](https://github.com/blog/1933-introducing-organization-webhooks), administrators will ideally only need one webhook to keep all of their directories in "sink".

## Basic Usage

1. In some accessible directory on your web server, clone down sink. For example:

    ```sh
    cd /var/www
    git clone https://github.com/mattdodge/sink.git
    ```

2. Copy `config.ini.example` to `config.ini` and modify its contents to match your desired implementation. See below for more documentation on configuration options.

    **Be sure to change your SECRET_PHRASE, you will need it in the next step.**

3. From your GitHub organization or from an individual repo, add a webhook to point to your recently cloned sync directory. Following up on our previous example:
    - **Payload URL**: *http://yourhost.com/sink*
    - **Content-Type**: *application/json*
    - **Secret**: *The secret phrase you configured in step 2*

## Security

Because sink has the possibility of getting *sooooo* popular, we need to think a little bit about the possibility of someone doing something malicious. The good news is that sink really shouldn't do anything unexpected. The only action it can take is resetting a folder that you have configured to match what is on a GitHub repository already. At the very least you should thank your attacker for bringing everything up to date for you. But, nonetheless, people are creative, so here are some security precautions you can/should take.

1. Change the `SECRET_PHRASE` in your `config.ini`. The script actually makes you do it (unless you change the script...), so just do it. It can't hurt.

2. Protect your `config.ini`. We don't want someone to be able to make a request to `http://yourhost/sink/config.ini` and see all of your tokens/keys/accounts in plaintext. The repository comes with a `.htaccess` file that will take care of this for you if you're on Apache. If you're on IIS, I'm sure there is some other way to make this work, I just have no idea how.

    Similarly, don't commit your config.ini if you are putting this on GitHub. The repo also comes with a `.gitignore` to help you with that. If you made a boo-boo and did that you probably should [take down your sensitive data](https://help.github.com/articles/remove-sensitive-data/).

3. Double-check your `RESET_MODE` on each sink. By default, the webhook action won't actually call a `git reset` on a directory. Sometimes it's useful to perform a reset before pulling though, in case a bad (read: lazy) user changed a file directly on the server, rather than committing and pushing like a good boy. 
  
