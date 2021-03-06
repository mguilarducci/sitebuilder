set :output, File.expand_path('log/whenever.log')

every 30.minute do
  command "php #{File.expand_path 'sitebuilder/script/publish_items.php'}"
end

every 15.minute do
  command "php #{File.expand_path 'sitebuilder/script/update_feeds.php'} low"
end

every 1.minute do
  command "php #{File.expand_path 'sitebuilder/script/update_feeds.php'} high"
end

every 10.minutes do
  command "php #{File.expand_path 'sitebuilder/script/import_csv.php'}"
end

every 1.day do
  command "php #{File.expand_path 'sitebuilder/script/update_events.php'} low"
end

every 1.minute do
  command "php #{File.expand_path 'sitebuilder/script/update_events.php'} high"
end

every 1.minute do
  command "php #{File.expand_path 'sitebuilder/script/perform_works.php'}"
end
