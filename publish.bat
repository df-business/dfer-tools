::发布
chcp 65001
@echo off
git add *
git commit -m ''
git push
::添加tag
git tag v4.7.4
::删除单个tag
::git tag -d v2.4
::tag批量删除,windows需要在git的bash模式下运行
::git tag|grep "v3"|xargs git tag -d
::查看所有tag
git tag
::上传所有tag
git push --tag

pause
exit