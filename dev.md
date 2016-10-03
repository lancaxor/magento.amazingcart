##On developer workstation
1) Edit the module code in master branch;

2) If someone changed a code in the repository -- we have to get these changes:

`git pull --commit`

3) Switch to 'product' branch:

`git checkout product`

If no branch 'product' exists on current machine, add flag -b:

`git checkout -b product`

4) Merge branches:

`git merge --commit master`

5) Push updates to server:

`git push origin product`

##On server

###Install

`git clone -b product https://alyashenko@bitbucket.org/serfcompany/amazing-cart-api-plugin-for-magento.git Amazingcard`

###Update

`git pull --commit`