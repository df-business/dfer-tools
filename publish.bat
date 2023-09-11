::发布
chcp 65001
@echo off
echo 一键发布...
set ver=4.8.5
git add *
git commit -m '%ver%'
git push
::添加tag
git tag %ver%
::删除单个tag
::git tag -d 2.4
::tag批量删除,windows需要在git的bash模式下运行
::git tag|grep "v3"|xargs git tag -d
::查看所有tag
::git tag
::上传所有tag
git push --tag
echo 发布完成
pause
exit