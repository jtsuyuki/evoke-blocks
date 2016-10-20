##Evoke Blocks

Evoke Blocks is a WordPress plugin that makes it easier to create reusable blocks of content to share across your pages.

It uses custom post types (CPTs) as the 'blocks' to hold the data and use the pages meta information to tie the cpt to the page. If then provides a bunch of methods you can use to pull the CPT content onto the proper page.


##CPT Templates
the plugin will look in your themes /templates directory for a template to render your CPT when it is included on a page.
The it will look for a file named with your CPT {type}-{slug}.php, if it doesn't find that it looks for {type}.php, if it doen't find that it will just output the contents of  the cpt->post_content.