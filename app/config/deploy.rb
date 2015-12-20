#logger.level = Logger::MAX_LEVEL

set :application, "set your application name here"
set :domain,      "dizzy.name"
set :deploy_to,   "/home/lostfilm/web/lf.dizzy.name/public_html"
set :app_path,    "app"

set :repository,  "https://github.com/dizzy7/lostfilm-notificator.git"
set :scm,         :git
set :user,        "lostfilm"
set :use_sudo,    false

#set :model_manager, "doctrine"

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set  :keep_releases,  5

set :shared_files,      ["app/config/parameters.yml"]
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor"]

set :use_composer, true
set :dump_assetic_assets, true
set :writable_dirs,       ["app/cache", "app/logs"]

set :branch do
  default_tag = `git tag`.split("\n").last

  tag = Capistrano::CLI.ui.ask "Tag to deploy (make sure to push the tag first): [#{default_tag}] "
  tag = default_tag if tag.empty?
  tag
end

namespace :deploy do
  task :mongo_session_index do
      capifony_pretty_print "--> Creating MongoDB session index"
      run "mongo lostfilm --eval \"db.Session.ensureIndex( { \"expires_at\": 1 }, { expireAfterSeconds: 0 } )\""
      capifony_puts_ok
  end

  task :mongo_schema_update do
      symfony.doctrine.mongodb.schema.update
  end

  task :brancrafed_bootstrap_install do
      capifony_pretty_print "--> Installing bootstrap fonts"
      run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} braincrafted:bootstrap:install #{console_options}'"
      capifony_puts_ok
  end
end

before "deploy:create_symlink", "deploy:mongo_session_index"
before "deploy:create_symlink", "deploy:mongo_schema_update"
before "deploy:create_symlink", "deploy:brancrafed_bootstrap_install"