"""Run against an EMPTY isolated test database; never against your working inventory."""
import json, urllib.request, urllib.error, http.cookiejar, sys
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8095'
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def call(action,data=None,token=None):
    headers={'Content-Type':'application/json'}
    if token: headers['X-CSRF-Token']=token
    req=urllib.request.Request(base+'/api.php?action='+action,data=json.dumps(data).encode() if data is not None else None,headers=headers)
    try:
        with client.open(req) as r:return r.status,json.load(r)
    except urllib.error.HTTPError as e:return e.code,json.load(e)
def check(ok,name):
    assert ok,name
    print('PASS:',name)
_,s=call('state');assert not s['products'],'Use an empty test database'
t=s['csrf']
check(call('product',{'sku':'A','name':'Test fabric','unit':'meter','min_stock':5})[0]==403,'CSRF rejects missing token')
check(call('product',{'sku':'A','name':'Test fabric','unit':'meter','min_stock':5},t)[0]==201,'Create product')
check(call('product',{'sku':'a','name':'Duplicate','unit':'pcs','min_stock':0},t)[0]==422,'Duplicate SKU is rejected case-insensitively')
_,s=call('state');pid=s['products'][0]['id']
def move(qty,kind='in',product=pid):return call('movement',{'product_id':product,'qty':qty,'kind':kind,'note':'Automated test'},t)
check(move(10)[0]==201,'Receive 10 units')
check(move(4,'out')[1]['balance']==6,'Issue 4 units; balance 6')
check(move(7,'out')[0]==422,'Reject issue exceeding balance')
_,s=call('state');check(s['products'][0]['stock']==6 and len(s['movements'])==2,'Rejected issue changes neither balance nor history')
for qty in [0,-1,1.5]:check(move(qty)[0]==422,'Reject invalid quantity '+str(qty))
check(move(1,product=999999)[0]==422,'Reject missing product')
check(move(1,kind='other')[0]==422,'Reject invalid transaction kind')
check(move(6,'out')[1]['balance']==0,'Allow exact balance withdrawal')
check(move(1,'out')[0]==422,'Cannot withdraw from zero stock')
check(call('demo',{},t)[0]==422,'Demo cannot overwrite existing data')
_,s=call('state');check(len(s['movements'])==3 and s['products'][0]['stock']==0,'Final ledger matches stock balance')
print('All 16 checks passed.')

